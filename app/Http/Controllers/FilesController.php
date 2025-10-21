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
            'Бошқарма',
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

    public function show(Request $request, UploadedFile $file): Response
    {
        $file->load('user');

        return Inertia::render('Files/Show', [
            'file' => $file,
            'user' => $request->user(),
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

            $message = "<b>📁 Fayl holati yangilandi!</b>\n\n" .
                "<b>Fayl nomi:</b> {$file->name}\n" .
                "<b>Asl fayl:</b> {$file->original_filename}\n" .
                "<b>Yangi holat:</b> {$statusText}\n" .
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
            $fullPath = storage_path('app/public/' . $filePath);

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
            $maxTelegramFileSize = 20 * 1024 * 1024; // 20MB

            // Check if file is too large for Telegram Bot API
            if ($file->file_size > $maxTelegramFileSize) {
                // Send file via Telegram instead of direct download
                $this->sendFileViaTelegram($file);

                return redirect()->back()->with('success', 'Large file is being sent to your Telegram. Please check your messages.');
            }

            if (! $file->telegram_file_id) {
                return redirect()->back()->with('error', 'File not available for download.');
            }

            $bot = TelegraphBot::first();

            if (! $bot || ! $bot->token) {
                return redirect()->back()->with('error', 'Bot configuration error.');
            }

            $botToken = $bot->token;

            // Get file info from Telegram with increased timeout
            $fileInfoResponse = Http::timeout(60)->get("https://api.telegram.org/bot{$botToken}/getFile", [
                'file_id' => $file->telegram_file_id,
            ]);

            if (! $fileInfoResponse->successful()) {
                Log::error('Failed to get file info from Telegram', [
                    'file_id' => $file->id,
                    'telegram_file_id' => $file->telegram_file_id,
                    'status' => $fileInfoResponse->status(),
                    'response' => $fileInfoResponse->body(),
                ]);

                return redirect()->back()->with('error', 'Could not retrieve file from Telegram. The file may be too large or no longer available.');
            }

            $fileInfo = $fileInfoResponse->json();

            if (! isset($fileInfo['result']['file_path'])) {
                Log::error('No file_path in Telegram response', [
                    'file_id' => $file->id,
                    'telegram_file_id' => $file->telegram_file_id,
                    'response' => $fileInfo,
                ]);

                return redirect()->back()->with('error', 'File path not available from Telegram.');
            }

            $filePath = $fileInfo['result']['file_path'];
            $fileSize = $fileInfo['result']['file_size'] ?? 0;

            // Check if file is too large (Telegram has a 2GB limit, but we'll be more conservative)
            if ($fileSize > 1.5 * 1024 * 1024 * 1024) { // 1.5GB
                return redirect()->back()->with('error', 'File is too large to download. Maximum size is 1.5GB.');
            }

            // Clean the filename for download
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->original_filename);

            // Use streaming download for large files
            if ($fileSize > 50 * 1024 * 1024) { // 50MB
                return $this->streamDownload($botToken, $filePath, $filename, $file->mime_type, $fileSize);
            }

            // For smaller files, use the original method but with increased timeout
            $fileResponse = Http::timeout(300)->get("https://api.telegram.org/file/bot{$botToken}/{$filePath}");

            if (! $fileResponse->successful()) {
                Log::error('Failed to download file from Telegram', [
                    'file_id' => $file->id,
                    'telegram_file_id' => $file->telegram_file_id,
                    'status' => $fileResponse->status(),
                    'file_size' => $fileSize,
                ]);

                return redirect()->back()->with('error', 'Could not download file from Telegram. Please try again.');
            }

            $fileContent = $fileResponse->body();

            if (empty($fileContent)) {
                Log::error('Empty file content received', [
                    'file_id' => $file->id,
                    'telegram_file_id' => $file->telegram_file_id,
                ]);

                return redirect()->back()->with('error', 'File content is empty.');
            }

            // Return file as download
            return response($fileContent)
                ->header('Content-Type', $file->mime_type ?: 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($fileContent))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to download file. Please try again.');
        }
    }

    /**
     * Stream download for large files to avoid memory issues
     */
    private function streamDownload($botToken, $filePath, $filename, $mimeType, $fileSize)
    {
        try {
            $url = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

            // Create a temporary file to stream the download
            $tempFile = tmpfile();
            if (! $tempFile) {
                throw new \Exception('Could not create temporary file');
            }

            $tempPath = stream_get_meta_data($tempFile)['uri'];

            // Download file in chunks
            $chunkSize = 8192; // 8KB chunks
            $handle = fopen($url, 'rb');

            if (! $handle) {
                throw new \Exception('Could not open file stream');
            }

            while (! feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk !== false) {
                    fwrite($tempFile, $chunk);
                }
            }

            fclose($handle);
            rewind($tempFile);

            // Return streaming response
            return response()->stream(function () use ($tempFile) {
                while (! feof($tempFile)) {
                    echo fread($tempFile, 8192);
                    flush();
                }
                fclose($tempFile);
            }, 200, [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => $fileSize,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            Log::error('Stream download failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);

            return redirect()->back()->with('error', 'Failed to download large file. Please try again.');
        }
    }

    private function sendFileViaTelegram(UploadedFile $file)
    {
        try {
            $bot = TelegraphBot::first();

            if (! $bot || ! $bot->token) {
                Log::error('Bot configuration not found for sending file via Telegram');

                return;
            }

            // Get the user who uploaded the file
            $user = $file->user;

            if (! $user || ! $user->telegram_id) {
                Log::error('User or Telegram ID not found', [
                    'file_id' => $file->id,
                    'user_id' => $user?->id,
                    'telegram_id' => $user?->telegram_id,
                ]);

                return;
            }

            // Send file via Telegram Bot API
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$bot->token}/sendDocument", [
                'chat_id' => $user->telegram_id,
                'document' => $file->telegram_file_id,
                'caption' => "📁 <b>Fayl yuklab olish</b>\n\n" .
                    "<b>Nomi:</b> {$file->name}\n" .
                    "<b>Fayl:</b> {$file->original_filename}\n" .
                    '<b>Hajmi:</b> ' . $this->formatFileSize($file->file_size) . "\n" .
                    "<b>ID:</b> #{$file->id}\n\n" .
                    '✅ Bu faylni web sahifasidan yuklab olish uchun yuborildi.',
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Large file sent via Telegram successfully', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                ]);
            } else {
                Log::error('Failed to send large file via Telegram', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending file via Telegram', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function destroy(UploadedFile $file)
    {
        $file->delete();

        return redirect()->route('files.index')->with('success', 'File deleted successfully.');
    }
}
