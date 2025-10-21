<?php

namespace App\Webhook;

use App\Models\UploadedFile;
use App\Models\User;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Telegram extends WebhookHandler
{
    protected function getRegions()
    {
        return [
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
    }

    public function start()
    {
        $chat = $this->message->chat();
        $user = $this->message->from();

        // Check if user exists by Telegram ID
        $existingUser = User::where('telegram_id', (string) $user->id())->first();

        if ($existingUser) {
            // User exists, send welcome back message with reply keyboard
            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make('📤 Yuklash'),
                    ReplyButton::make('📁 Fayllar'),
                    ReplyButton::make('💡 Yordam'),
                ])
                ->row([
                    ReplyButton::make('🔄 Yangilash'),
                ]);

            $this->chat->message("<b>Salom {$existingUser->name}!</b> Qaytganingizdan xursandmiz! 🎉\n\nQuyidagi tugmalardan birini tanlang:")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        // User doesn't exist, request phone number for registration
        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('📱 Telefon raqamini ulashish')->requestContact(),
            ]);

        // Send message with reply keyboard
        $this->chat->message("<b>Xush kelibsiz!</b> 👋\n\nBoshlash uchun ro'yxatdan o'tishingiz kerak. Iltimos, quyidagi tugma orqali telefon raqamingizni ulashing.")
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    public function help()
    {
        $helpText = "🤖 <b>Mavjud buyruqlar:</b>\n\n" .
            "📤 <b>Fayl yuklash</b> - Admin ko'rib chiqishi uchun fayl yuklash\n" .
            "📁 <b>Fayllarim</b> - Yuklangan fayllar holati\n" .
            "💡 <b>Yordam</b> - Ushbu yordam xabarini ko'rsatish\n" .
            "🔄 <b>Yangilash</b> - Botni qayta ishga tushirish\n\n" .
            "<b>📤 Yuklash jarayoni:</b>\n" .
            "1. 📤 Fayl yuklash tugmasini bosing\n" .
            "2. Faylingiz uchun nom/tavsif bering\n" .
            "3. Faylingizni yuklang (hujjat, rasm, video, audio, ovozli xabar)\n" .
            "4. Fayl adminlarga ko'rib chiqish uchun yuboriladi\n\n" .
            "<b>📁 Qo'llab-quvvatlanadigan fayl turlari:</b>\n" .
            "• Hujjatlar (PDF, DOC va boshqalar)\n" .
            "• Rasmlar (JPG, PNG va boshqalar)\n" .
            "• Videolar (MP4, AVI va boshqalar)\n" .
            "• Audio fayllar (MP3, WAV va boshqalar)\n" .
            '• Ovozli xabarlar';

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('📤 Yuklash'),
                ReplyButton::make('📁 Fayllar'),
                ReplyButton::make('🏠 Bosh'),
            ]);

        $this->chat->message($helpText)
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    public function files()
    {
        $user = $this->message->from();

        // Find existing user by telegram_id
        $existingUser = User::where('telegram_id', $user->id())->first();

        if (! $existingUser) {
            $this->chat->message("<b>❌ Xatolik!</b>\n\nSiz hali ro'yxatdan o'tmagansiz. Iltimos, avval /start buyrug'ini yuboring va ro'yxatdan o'ting.")
                ->html()
                ->send();

            return;
        }

        // Get user's uploaded files
        $files = UploadedFile::where('user_id', $existingUser->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($files->isEmpty()) {
            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make('📤 Yuklash'),
                    ReplyButton::make('🔄 Yangilash'),
                    ReplyButton::make('🏠 Bosh'),
                ]);

            $this->chat->message("<b>📁 Fayllar</b>\n\nSiz hali hech qanday fayl yuklamagansiz.\n\nQuyidagi tugmalardan birini tanlang:")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        $message = "<b>📁 Sizning fayllaringiz</b>\n\n";

        foreach ($files as $file) {
            $statusIcon = match ($file->status) {
                'accepted' => '✅',
                'rejected' => '❌',
                'pending' => '⏳',
                default => '📄'
            };

            $statusText = match ($file->status) {
                'accepted' => 'Tasdiqlandi',
                'rejected' => 'Rad etildi',
                'pending' => 'Jarayonda',
                default => 'Noma\'lum'
            };

            $message .= "{$statusIcon} <b>{$file->name}</b>\n";
            $message .= "   📄 {$file->original_filename}\n";
            $message .= "   📊 Holat: {$statusText}\n";
            $message .= '   📅 ' . $file->created_at->format('d.m.Y H:i') . "\n";

            if ($file->admin_notes) {
                $message .= "   💬 Izoh: {$file->admin_notes}\n";
            }

            $message .= "\n";
        }

        if ($files->count() >= 10) {
            $totalFiles = UploadedFile::where('user_id', $existingUser->id)->count();
            $message .= "<i>So'nggi 10 ta fayl ko'rsatildi. Jami: {$totalFiles} ta fayl</i>\n\n";
        }

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('📤 Yangi'),
                ReplyButton::make('🔄 Yangilash'),
                ReplyButton::make('🏠 Bosh'),
            ]);

        $this->chat->message($message)
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    public function upload()
    {
        $user = $this->message->from();

        // Check if user is registered
        $existingUser = User::where('telegram_id', (string) $user->id())->first();

        if (! $existingUser) {
            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make("🏠 Ro'yxatdan o'tish"),
                ]);

            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nIltimos, avval ro'yxatdan o'ting.")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        // Store user state for upload process using cache
        Cache::put('telegram_upload_state_' . $user->id(), 'waiting_for_name', 3600);
        Cache::put('telegram_user_id_' . $user->id(), $existingUser->id, 3600);

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('❌ Bekor qilish'),
            ]);

        $this->chat->message("<b>📤 Fayl yuklash</b>\n\nIltimos, faylingiz uchun nom yoki tavsif kiriting:\n\n<i>Yuklash jarayonini bekor qilish uchun tugmani bosing.</i>")
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    public function cancel()
    {
        $user = $this->message->from();

        // Check if user is in upload state
        if (Cache::get('telegram_upload_state_' . $user->id())) {
            Cache::forget('telegram_upload_state_' . $user->id());
            Cache::forget('telegram_user_id_' . $user->id());
            Cache::forget('telegram_file_name_' . $user->id());

            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make('📤 Yuklash'),
                    ReplyButton::make('📁 Fayllar'),
                    ReplyButton::make('🏠 Bosh'),
                ]);

            $this->chat->message("<b>❌ Bekor qilindi</b>\n\nFayl yuklash bekor qilindi. Quyidagi tugmalardan birini tanlang:")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('📤 Yuklash'),
                ReplyButton::make('📁 Fayllar'),
                ReplyButton::make('🏠 Bosh'),
            ]);

        $this->chat->message("<b>ℹ️ Ma'lumot</b>\n\nBekor qilinadigan faol yuklash yo'q. Quyidagi tugmalardan birini tanlang:")
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    protected function handleMessage(): void
    {
        $user = $this->message->from();
        $uploadState = Cache::get('telegram_upload_state_' . $user->id());
        $messageText = $this->message?->text();

        // Debug logging
        Log::info('HandleMessage called', [
            'hasContact' => $this->message?->contact() !== null,
            'uploadState' => $uploadState,
            'messageText' => $messageText,
            'hasDocument' => $this->message?->document() !== null,
            'userId' => Cache::get('telegram_user_id_' . $user->id()),
        ]);

        // Check if this is a contact message
        if ($this->message?->contact() !== null) {
            $this->handleContact();

            return;
        }

        // Handle reply keyboard button presses
        if ($messageText) {
            switch ($messageText) {
                case '📤 Yuklash':
                case '📤 Yangi':
                    $this->upload();

                    return;
                case '📁 Fayllar':
                case '📁 Fayllarim':
                    $this->files();

                    return;
                case '💡 Yordam':
                    $this->help();

                    return;
                case '🔄 Yangilash':
                    $this->files();

                    return;
                case '🏠 Bosh':
                case '🏠 Bosh sahifa':
                    $this->start();

                    return;
                case '❌ Bekor qilish':
                    $this->cancel();

                    return;
            }
        }

        // Check if user is in registration process
        $tempUserData = Cache::get('telegram_temp_user_' . $user->id());
        if ($tempUserData && isset($tempUserData['step'])) {
            if ($tempUserData['step'] === 'waiting_for_name') {
                $this->handleFullNameInput();

                return;
            }
        }

        // Check if user is in upload state
        if ($uploadState === 'waiting_for_name') {
            Log::info('Routing to handleUploadName');
            $this->handleUploadName();

            return;
        }

        if ($uploadState === 'waiting_for_file') {
            Log::info('Routing to handleUploadFile');
            $this->handleUploadFile();

            return;
        }

        // Call parent method for other message handling
        Log::info('Routing to parent handleMessage');
        parent::handleMessage();
    }

    protected function handleUploadName(): void
    {
        $user = $this->message->from();
        $fileName = $this->message->text();
        $userId = Cache::get('telegram_user_id_' . $user->id());

        // Debug logging
        Log::info('HandleUploadName called', [
            'fileName' => $fileName,
            'userId' => $userId,
            'uploadState' => Cache::get('telegram_upload_state_' . $user->id()),
        ]);

        // Check for cancel command
        if (strtolower(trim($fileName)) === '/cancel' || strtolower(trim($fileName)) === '❌ bekor qilish') {
            $this->cancel();

            return;
        }

        if (empty(trim($fileName))) {
            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make('❌ Bekor qilish'),
                ]);

            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nIltimos, faylingiz uchun to'g'ri nom kiriting yoki bekor qilish tugmasini bosing.")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        // Store file name and update state
        Cache::put('telegram_file_name_' . $user->id(), trim($fileName), 3600);
        Cache::put('telegram_upload_state_' . $user->id(), 'waiting_for_file', 3600);

        // Debug logging
        Log::info('Upload state updated', [
            'fileName' => trim($fileName),
            'newState' => 'waiting_for_file',
        ]);

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('❌ Bekor qilish'),
            ]);

        $this->chat->message("<b>✅ Ajoyib!</b>\n\nEndi iltimos faylingizni yuklang (hujjat, rasm, video, audio va boshqalar):\n\n<i>Yuklash jarayonini bekor qilish uchun tugmani bosing.</i>")
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }

    protected function handleUploadFile(): void
    {
        $user = $this->message->from();
        $existingUser = User::where('telegram_id', (string) $user->id())->first();
        $fileName = Cache::get('telegram_file_name_' . $user->id());
        $userId = Cache::get('telegram_user_id_' . $user->id());

        // Check if message contains text (might be a command)
        if ($this->message->text()) {
            $text = trim($this->message->text());
            if (strtolower($text) === '/cancel' || strtolower($text) === '❌ bekor qilish') {
                $this->cancel();

                return;
            }

            $keyboard = ReplyKeyboard::make()
                ->row([
                    ReplyButton::make('❌ Bekor qilish'),
                ]);

            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nIltimos, to'g'ri fayl yuklang (hujjat, rasm, video, audio va boshqalar) yoki bekor qilish tugmasini bosing.")
                ->html()
                ->replyKeyboard($keyboard)
                ->send();

            return;
        }

        // Send wait message and store message ID for later editing
        $waitMessage = $this->chat->message("<b>⏳ Kutib turing...</b>\n\nFaylingiz qayta ishlanmoqda...")
            ->html()
            ->send();

        // Check if message contains a file
        $file = $this->getFileFromMessage();

        if (! $file) {
            // Edit wait message with error info
            $debugInfo = "<b>🔍 Fayl yuklash muammosi</b>\n\n";
            $debugInfo .= "Faylingizni oldim, lekin to'g'ri qayta ishlay olmadim.\n";
            $debugInfo .= "<b>Fayl nomi:</b> {$fileName}\n";
            $debugInfo .= '<b>Xabar turi:</b> ' . ($this->message->document() ? 'Hujjat' : 'Noma\'lum') . "\n\n";
            $debugInfo .= "<b>Quyidagilarni sinab ko'ring:</b>\n";
            $debugInfo .= "• Boshqa fayl yuklash\n";
            $debugInfo .= "• /cancel ishlatib qaytadan urinish\n";
            $debugInfo .= "• Muammo davom etsa, qo'llab-quvvatlash bilan bog'lanish";

            $this->chat->edit($waitMessage->telegraphMessageId())->message($debugInfo)->html()->send();

            return;
        }

        // Process the upload and edit the wait message with success
        $this->processFileUpload($existingUser, $fileName, $file, $waitMessage->telegraphMessageId());

        // Reset upload state
        Cache::forget('telegram_upload_state_' . $user->id());
        Cache::forget('telegram_user_id_' . $user->id());
        Cache::forget('telegram_file_name_' . $user->id());
    }

    protected function getFileFromMessage()
    {
        // Check for different file types
        if ($this->message->document()) {
            $document = $this->message->document();

            return [
                'type' => 'document',
                'file_id' => $document->id(),
                'file_name' => $document->filename() ?: 'document',
                'mime_type' => $document->mimeType() ?: 'application/octet-stream',
                'file_size' => $document->filesize() ?: 0,
            ];
        }

        if ($this->message->photos() && $this->message->photos()->isNotEmpty()) {
            $photos = $this->message->photos();
            $largestPhoto = $photos->last(); // Get highest quality photo

            return [
                'type' => 'photo',
                'file_id' => $largestPhoto->id(),
                'file_name' => 'photo.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => $largestPhoto->filesize() ?: 0,
            ];
        }

        if ($this->message->video()) {
            $video = $this->message->video();

            return [
                'type' => 'video',
                'file_id' => $video->id(),
                'file_name' => $video->filename() ?: 'video.mp4',
                'mime_type' => $video->mimeType() ?: 'video/mp4',
                'file_size' => $video->filesize() ?: 0,
            ];
        }

        if ($this->message->audio()) {
            $audio = $this->message->audio();

            return [
                'type' => 'audio',
                'file_id' => $audio->id(),
                'file_name' => $audio->filename() ?: 'audio.mp3',
                'mime_type' => $audio->mimeType() ?: 'audio/mpeg',
                'file_size' => $audio->filesize() ?: 0,
            ];
        }

        if ($this->message->voice()) {
            $voice = $this->message->voice();

            return [
                'type' => 'voice',
                'file_id' => $voice->id(),
                'file_name' => 'voice.ogg',
                'mime_type' => 'audio/ogg',
                'file_size' => $voice->filesize() ?: 0,
            ];
        }

        // Debug: Let's see what we actually have
        $debugInfo = "Debug: Message type not recognized.\n";
        $debugInfo .= 'Has document: ' . ($this->message->document() ? 'Yes' : 'No') . "\n";
        $debugInfo .= 'Has photos: ' . ($this->message->photos() ? 'Yes' : 'No') . "\n";
        $debugInfo .= 'Has video: ' . ($this->message->video() ? 'Yes' : 'No') . "\n";
        $debugInfo .= 'Has audio: ' . ($this->message->audio() ? 'Yes' : 'No') . "\n";
        $debugInfo .= 'Has voice: ' . ($this->message->voice() ? 'Yes' : 'No') . "\n";
        $debugInfo .= 'Has text: ' . ($this->message->text() ? 'Yes' : 'No') . "\n";

        // Log this for debugging
        Log::info('Telegram file upload debug', ['debug_info' => $debugInfo]);

        return null;
    }

    protected function processFileUpload($user, $fileName, $file, $waitMessageId = null)
    {
        try {
            // Check file size limits
            $maxFileSize = 1.5 * 1024 * 1024 * 1024; // 1.5GB
            if ($file['file_size'] > $maxFileSize) {
                $errorMessage = "<b>❌ Fayl juda katta!</b>\n\n" .
                    "Fayl hajmi: " . $this->formatFileSize($file['file_size']) . "\n" .
                    "Maksimal ruxsat etilgan hajm: " . $this->formatFileSize($maxFileSize) . "\n\n" .
                    "Iltimos, kichikroq fayl yuboring.";

                if ($waitMessageId) {
                    $this->chat->edit($waitMessageId)->message($errorMessage)->html()->send();
                } else {
                    $this->chat->message($errorMessage)->html()->send();
                }
                return;
            }

            // Get Telegram file path (but don't download the file)
            $telegramFilePath = $this->getTelegramFilePath($file['file_id']);

            if (!$telegramFilePath) {
                $errorMessage = "<b>❌ Xatolik yuz berdi!</b>\n\n" .
                    "Fayl ma'lumotlarini olishda xatolik yuz berdi. " .
                    "Fayl juda katta bo'lishi mumkin yoki Telegram serverida muammo bor.\n\n" .
                    "Iltimos, kichikroq fayl yuboring yoki keyinroq qaytadan urinib ko'ring.";

                if ($waitMessageId) {
                    $this->chat->edit($waitMessageId)->message($errorMessage)->html()->send();
                } else {
                    $this->chat->message($errorMessage)->html()->send();
                }
                return;
            }

            // Save file info to database (keeping file on Telegram servers)
            $uploadedFile = UploadedFile::create([
                'user_id' => $user->id,
                'name' => $fileName,
                'original_filename' => $file['file_name'],
                'telegram_file_id' => $file['file_id'],
                'file_path' => $telegramFilePath, // Use the actual Telegram file path
                'file_type' => $file['type'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
                'status' => 'pending',
            ]);

            // Send success message
            $fileInfo = "<b>📁 Fayl muvaffaqiyatli yuklandi!</b>\n\n" .
                "<b>Nomi:</b> {$fileName}\n" .
                "<b>Turi:</b> {$file['type']}\n" .
                "<b>Fayl:</b> {$file['file_name']}\n" .
                '<b>Hajmi:</b> ' . $this->formatFileSize($file['file_size']) . "\n" .
                "<b>Foydalanuvchi:</b> {$user->name}\n" .
                "<b>Yuklash ID:</b> #{$uploadedFile->id}\n\n" .
                "✅ Faylingiz Telegramda saqlandi va adminlarga ko'rib chiqish uchun yuborildi.";

            if ($waitMessageId) {
                // Edit the wait message with success info
                $this->chat->edit($waitMessageId)->message($fileInfo)->html()->send();
            } else {
                // Send new message if no wait message ID provided
                $this->chat->message($fileInfo)->html()->send();
            }

            // Log successful upload
            Log::info('File uploaded successfully', [
                'user_id' => $user->id,
                'file_id' => $uploadedFile->id,
                'telegram_file_id' => $file['file_id'],
                'telegram_file_path' => $telegramFilePath,
            ]);
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $file,
            ]);

            $errorMessage = "<b>❌ Xatolik yuz berdi!</b>\n\nKechirasiz, fayl ma'lumotlarini saqlashda xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring yoki qo'llab-quvvatlash bilan bog'laning.";

            if ($waitMessageId) {
                // Edit the wait message with error info
                $this->chat->edit($waitMessageId)->message($errorMessage)->html()->send();
            } else {
                // Send new message if no wait message ID provided
                $this->chat->message($errorMessage)->html()->send();
            }
        }
    }

    protected function getTelegramFilePath($fileId)
    {
        try {
            // Get file info from Telegram API (just for the path, not downloading)
            $response = Http::timeout(60)->get("https://api.telegram.org/bot{$this->bot->token}/getFile", [
                'file_id' => $fileId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['result']['file_path'])) {
                    return $data['result']['file_path']; // Return Telegram's file path
                }
            } else {
                Log::error('Failed to get Telegram file path', [
                    'file_id' => $fileId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Telegram file path', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function formatFileSize($bytes)
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

    public function handleContact()
    {
        $contact = $this->message->contact();
        $user = $this->message->from();

        if (! $contact || ! $contact->phoneNumber()) {
            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nKechirasiz, telefon raqamingizni ololmadim. Iltimos, qaytadan urinib ko'ring.")
                ->html()
                ->send();

            return;
        }

        $phoneNumber = $contact->phoneNumber();
        $telegramUsername = $user->username() ?? $user->firstName();
        $telegramId = (string) $user->id();

        // Check if user already exists with this phone number
        $existingUser = User::where('phone_number', $phoneNumber)->first();

        if ($existingUser) {
            // Update the existing user's telegram_id for future identification
            $existingUser->update(['telegram_id' => $telegramId]);
            $this->chat->message("<b>🎉 Xush kelibsiz, {$existingUser->name}!</b>\n\nHisobingiz Telegram bilan bog'landi.")
                ->html()
                ->send();

            return;
        }

        // Store user data temporarily for name input
        Cache::put('telegram_temp_user_' . $user->id(), [
            'phone_number' => $phoneNumber,
            'telegram_username' => $telegramUsername,
            'telegram_id' => $telegramId,
            'step' => 'waiting_for_name',
        ], 3600);

        // Ask for full name
        $this->askForFullName();
    }

    protected function askForFullName()
    {
        $this->chat->message("<b>👤 To'liq ismingizni kiriting</b>\n\nIltimos, to'liq ism va familiyangizni kiriting:\n\n<i>Masalan: Halimjon Hikmatjonov</i>")
            ->html()
            ->send();
    }

    protected function handleFullNameInput()
    {
        $user = $this->message->from();
        $fullName = trim($this->message->text());
        $tempUserData = Cache::get('telegram_temp_user_' . $user->id());

        if (! $tempUserData) {
            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nRo'yxatdan o'tish vaqti tugagan. Iltimos, /start buyrug'i bilan qaytadan boshlang.")
                ->html()
                ->send();

            return;
        }

        // Validate full name
        if (empty($fullName) || strlen($fullName) < 2) {
            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nIltimos, to'liq ism va familiyangizni kiriting.\n\n<i>Masalan: Halimjon Hikmatjonov</i>")
                ->html()
                ->send();

            return;
        }

        // Update temp data with full name and move to region selection
        $tempUserData['full_name'] = $fullName;
        $tempUserData['step'] = 'waiting_for_region';
        Cache::put('telegram_temp_user_' . $user->id(), $tempUserData, 3600);

        // Show region selection
        $this->showRegionSelection();
    }

    protected function showRegionSelection()
    {
        $regions = $this->getRegions();
        $keyboard = Keyboard::make();

        // Get current user ID from the message context
        $user = $this->message->from();
        $userId = $user->id();

        // Create inline keyboard with regions (2 per row)
        $buttons = [];
        foreach ($regions as $index => $region) {
            $buttons[] = Button::make($region)->action('region_' . $index)->param('user_id', $userId);

            // Add row every 2 buttons
            if (count($buttons) == 2 || $index == count($regions) - 1) {
                $keyboard->row($buttons);
                $buttons = [];
            }
        }

        // Add skip option
        $keyboard->row([
            Button::make("⏭️ O'tkazib yuborish")->action('region_skip')->param('user_id', $userId),
        ]);

        $this->chat->message("<b>📍 Hududingizni tanlang</b>\n\nIltimos, qaysi hududda yashashingizni tanlang yoki o'tkazib yuboring:")
            ->html()
            ->keyboard($keyboard)
            ->send();
    }

    public function region_skip()
    {
        $this->handleRegionSelection('region_skip');
    }

    public function region_0()
    {
        $this->handleRegionSelection('region_0');
    }

    public function region_1()
    {
        $this->handleRegionSelection('region_1');
    }

    public function region_2()
    {
        $this->handleRegionSelection('region_2');
    }

    public function region_3()
    {
        $this->handleRegionSelection('region_3');
    }

    public function region_4()
    {
        $this->handleRegionSelection('region_4');
    }

    public function region_5()
    {
        $this->handleRegionSelection('region_5');
    }

    public function region_6()
    {
        $this->handleRegionSelection('region_6');
    }

    public function region_7()
    {
        $this->handleRegionSelection('region_7');
    }

    public function region_8()
    {
        $this->handleRegionSelection('region_8');
    }

    public function region_9()
    {
        $this->handleRegionSelection('region_9');
    }

    public function region_10()
    {
        $this->handleRegionSelection('region_10');
    }

    public function region_11()
    {
        $this->handleRegionSelection('region_11');
    }

    public function region_12()
    {
        $this->handleRegionSelection('region_12');
    }

    public function region_13()
    {
        $this->handleRegionSelection('region_13');
    }

    public function region_14()
    {
        $this->handleRegionSelection('region_14');
    }

    public function region_15()
    {
        $this->handleRegionSelection('region_15');
    }

    public function region_16()
    {
        $this->handleRegionSelection('region_16');
    }

    public function region_17()
    {
        $this->handleRegionSelection('region_17');
    }

    public function region_18()
    {
        $this->handleRegionSelection('region_18');
    }

    protected function handleRegionSelection($callbackData)
    {
        // Get user from data property (Telegraph provides this for button actions)
        $userId = $this->data->get('user_id');

        if (! $userId) {
            Log::error('No user ID found in button action data');

            return;
        }

        $tempUserData = Cache::get('telegram_temp_user_' . $userId);

        if (! $tempUserData) {
            $this->chat->message("<b>⚠️ Xatolik!</b>\n\nRo'yxatdan o'tish vaqti tugagan. Iltimos, /start buyrug'i bilan qaytadan boshlang.")
                ->html()
                ->send();

            return;
        }

        $selectedRegion = null;

        if ($callbackData === 'region_skip') {
            $selectedRegion = null; // Region will be nullable
        } else {
            $regionIndex = (int) str_replace('region_', '', $callbackData);
            $regions = $this->getRegions();

            if (isset($regions[$regionIndex])) {
                $selectedRegion = $regions[$regionIndex];
            }
        }

        // Create new user with phone number, full name and region
        $newUser = User::create([
            'name' => $tempUserData['full_name'] ?? $tempUserData['telegram_username'],
            'email' => $tempUserData['phone_number'] . '@telegram.local',
            'phone_number' => $tempUserData['phone_number'],
            'region' => $selectedRegion,
            'telegram_id' => $tempUserData['telegram_id'],
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
        ]);

        // Clean up temporary data
        Cache::forget('telegram_temp_user_' . $userId);

        // Send success message with action buttons
        $regionText = $selectedRegion ? "\n<b>Hudud:</b> {$selectedRegion}" : '';

        $keyboard = ReplyKeyboard::make()
            ->row([
                ReplyButton::make('📤 Yuklash'),
                ReplyButton::make('📁 Fayllar'),
                ReplyButton::make('💡 Yordam'),
            ])
            ->row([
                ReplyButton::make('🔄 Yangilash'),
            ]);

        $this->chat->message("<b>🎉 Ajoyib!</b>\n\n<b>Ro'yxatdan o'tish muvaffaqiyatli yakunlandi!</b>\n\n<b>Ism:</b> {$newUser->name}{$regionText}\n<b>Telefon:</b> {$newUser->phone_number}\n\nXizmatimizga xush kelibsiz!\n\nQuyidagi tugmalardan birini tanlang:")
            ->html()
            ->replyKeyboard($keyboard)
            ->send();
    }
}
