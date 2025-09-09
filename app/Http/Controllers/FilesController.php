<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FilesController extends Controller
{
    public function index(Request $request): Response
    {
        $query = UploadedFile::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by region if provided
        if ($request->has('region') && $request->region) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('region', $request->region);
            });
        }

        // Filter by date range - default to today if not provided
        $dateFrom = $request->date_from ?: now()->format('Y-m-d');
        $dateTo = $request->date_to ?: now()->format('Y-m-d');

        $query->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        // Search by name or user name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $files = $query->paginate(15)->withQueryString();

        // Get predefined regions list
        $regions = [
            'Қувасой шахар',
            'Фарғона шахар',
            'Қўқон шахар',
            'Марғилон шахар',
            'Бешариқ туман',
            'Боғдод туман',
            'Бувайда туман',
            'Данғара туман',
            'Ёзёвон туман',
            'Қува туман',
            'Олтиариқ туман',
            'Қўштепа туман',
            'Риштон туман',
            'Тошлоқ туман',
            'Ўзбекистон туман',
            'Учкўприк туман',
            'Фарғона туман',
            'Фурқат туман',
            'Сўх туман',
        ];

        return Inertia::render('Files/Index', [
            'files' => $files,
            'filters' => [
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
                'region' => $request->region ?? '',
                'date_from' => $request->date_from ?? now()->format('Y-m-d'),
                'date_to' => $request->date_to ?? now()->format('Y-m-d'),
            ],
            'regions' => $regions,
            'stats' => [
                'total' => UploadedFile::count(),
                'pending' => UploadedFile::where('status', 'pending')->count(),
                'waiting' => UploadedFile::where('status', 'waiting')->count(),
                'accepted' => UploadedFile::where('status', 'accepted')->count(),
                'rejected' => UploadedFile::where('status', 'rejected')->count(),
            ],
            'user' => $request->user(),
        ]);
    }

    public function show(UploadedFile $file): Response
    {
        $file->load('user');

        return Inertia::render('Files/Show', [
            'file' => $file,
        ]);
    }

    public function updateStatus(Request $request, UploadedFile $file)
    {
        $validationRules = [
            'status' => 'required|in:pending,waiting,accepted,rejected',
            'admin_notes' => 'nullable|string|max:1000',
            'feedback_files.*' => 'nullable|file|max:20480', // 20MB max per file
        ];

        // Add validation for accepted status fields
        if ($request->status === 'accepted') {
            $validationRules['registered_count'] = 'required|integer|min:0';
            $validationRules['not_registered_count'] = 'required|integer|min:0';
            $validationRules['accepted_note'] = 'nullable|string|max:1000';
        }

        $request->validate($validationRules);

        $oldStatus = $file->status;

        $updateData = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ];

        // Add accepted status fields if status is accepted
        if ($request->status === 'accepted') {
            $updateData['registered_count'] = $request->registered_count;
            $updateData['not_registered_count'] = $request->not_registered_count;
            $updateData['accepted_note'] = $request->accepted_note;
        }

        $file->update($updateData);

        // Handle multiple feedback files upload if provided
        $feedbackFilePaths = [];
        if ($request->hasFile('feedback_files')) {
            foreach ($request->file('feedback_files') as $feedbackFile) {
                if ($feedbackFile && $feedbackFile->isValid()) {
                    $feedbackFilePaths[] = $feedbackFile->store('feedback_files', 'public');
                }
            }
        }

        // Send Telegram notification if status changed
        if ($oldStatus !== $request->status) {
            $this->sendStatusNotification($file, $request->status, $request->admin_notes, $feedbackFilePaths);
        }

        return redirect()->back()->with('success', 'File status updated successfully.');
    }

    private function sendStatusNotification(UploadedFile $file, string $newStatus, ?string $adminNotes = null, array $feedbackFilePaths = [])
    {
        try {
            // Get bot token from database
            $bot = TelegraphBot::first();

            if (! $bot || ! $bot->token || ! $file->user->telegram_id) {
                Log::warning('Cannot send status notification', [
                    'file_id' => $file->id,
                    'user_id' => $file->user->id,
                    'has_bot' => ! empty($bot),
                    'has_telegram_id' => ! empty($file->user->telegram_id),
                ]);

                return;
            }

            // Prepare status message in Uzbek
            $statusText = match ($newStatus) {
                'accepted' => '✅ <b>Tasdiqlandi</b>',
                'rejected' => '❌ <b>Rad etildi</b>',
                'pending' => '⏳ <b>Jarayonda</b>',
                'waiting' => "🏢 <b>Bino inshoat bo'limiga yuborildi</b>",
                default => '📄 <b>Yangilandi</b>'
            };

            $message = "<b>📁 Fayl holati yangilandi!</b>\n\n".
                "<b>Fayl nomi:</b> {$file->name}\n".
                "<b>Asl fayl:</b> {$file->original_filename}\n".
                "<b>Yangi holat:</b> {$statusText}\n".
                "<b>Fayl ID:</b> #{$file->id}";

            if ($adminNotes) {
                $message .= "\n\n<b>💬 Admin izohi:</b>\n{$adminNotes}";
            }

            $message .= "\n\n<i>Barcha fayllaringizni ko'rish uchun /files buyrug'ini yuboring.</i>";

            // Send text notification first
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$bot->token}/sendMessage", [
                'chat_id' => $file->user->telegram_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            // Send feedback files if provided
            if (! empty($feedbackFilePaths) && $response->successful()) {
                foreach ($feedbackFilePaths as $feedbackFilePath) {
                    $this->sendFeedbackFile($bot->token, $file->user->telegram_id, $feedbackFilePath);
                    // Add a small delay between files to avoid rate limiting
                    usleep(500000); // 0.5 second delay
                }
            }

            if ($response->successful()) {
                Log::info('Status notification sent successfully', [
                    'file_id' => $file->id,
                    'user_id' => $file->user->id,
                    'new_status' => $newStatus,
                    'feedback_files_count' => count($feedbackFilePaths),
                ]);
            } else {
                Log::error('Failed to send status notification', [
                    'file_id' => $file->id,
                    'user_id' => $file->user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending status notification', [
                'file_id' => $file->id,
                'user_id' => $file->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendFeedbackFile(string $botToken, string $chatId, string $filePath)
    {
        try {
            $fullPath = storage_path('app/public/'.$filePath);

            if (! file_exists($fullPath)) {
                Log::error('Feedback file not found', ['path' => $fullPath]);

                return;
            }

            $fileName = basename($filePath);
            $mimeType = mime_content_type($fullPath);

            // Determine the appropriate Telegram API method based on file type
            $method = 'sendDocument'; // Default to document

            if (str_starts_with($mimeType, 'image/')) {
                $method = 'sendPhoto';
                $fileParam = 'photo';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $method = 'sendVideo';
                $fileParam = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $method = 'sendAudio';
                $fileParam = 'audio';
            } else {
                $fileParam = 'document';
            }

            // Send file to Telegram
            $response = Http::timeout(60)->attach(
                $fileParam,
                file_get_contents($fullPath),
                $fileName
            )->post("https://api.telegram.org/bot{$botToken}/{$method}", [
                'chat_id' => $chatId,
                'caption' => '📎 <b>Admin javob fayli</b>',
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Feedback file sent successfully', [
                    'file_path' => $filePath,
                    'chat_id' => $chatId,
                    'method' => $method,
                ]);

                // Clean up the file after sending
                unlink($fullPath);
            } else {
                Log::error('Failed to send feedback file', [
                    'file_path' => $filePath,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending feedback file', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function download(UploadedFile $file)
    {
        try {
            // Get the bot token from database
            $bot = TelegraphBot::first();

            if (! $file->telegram_file_id) {
                return redirect()->back()->with('error', 'File not available for download.');
            }

            if (! $bot || ! $bot->token) {
                return redirect()->back()->with('error', 'Bot configuration error.');
            }

            $botToken = $bot->token;

            // Get file info from Telegram
            $fileInfoResponse = Http::timeout(30)->get("https://api.telegram.org/bot{$botToken}/getFile", [
                'file_id' => $file->telegram_file_id,
            ]);

            if (! $fileInfoResponse->successful()) {
                Log::error('Failed to get file info from Telegram', [
                    'file_id' => $file->id,
                    'status' => $fileInfoResponse->status(),
                ]);

                return redirect()->back()->with('error', 'Could not retrieve file from Telegram.');
            }

            $fileInfo = $fileInfoResponse->json();

            if (! isset($fileInfo['result']['file_path'])) {
                Log::error('No file_path in Telegram response', ['file_id' => $file->id]);

                return redirect()->back()->with('error', 'File path not available.');
            }

            $filePath = $fileInfo['result']['file_path'];

            // Download file from Telegram
            $fileResponse = Http::timeout(60)->get("https://api.telegram.org/file/bot{$botToken}/{$filePath}");

            if (! $fileResponse->successful()) {
                Log::error('Failed to download file from Telegram', [
                    'file_id' => $file->id,
                    'status' => $fileResponse->status(),
                ]);

                return redirect()->back()->with('error', 'Could not download file from Telegram.');
            }

            $fileContent = $fileResponse->body();

            if (empty($fileContent)) {
                Log::error('Empty file content received', ['file_id' => $file->id]);

                return redirect()->back()->with('error', 'File content is empty.');
            }

            // Clean the filename for download
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->original_filename);

            // Return file as download
            return response($fileContent)
                ->header('Content-Type', $file->mime_type ?: 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
                ->header('Content-Length', strlen($fileContent))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to download file. Please try again.');
        }
    }

    public function destroy(UploadedFile $file)
    {
        $file->delete();

        return redirect()->route('files.index')->with('success', 'File deleted successfully.');
    }
}
