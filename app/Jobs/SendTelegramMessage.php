<?php

namespace App\Jobs;

use App\Models\TelegramMessage;
use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $senderId,
        public int $recipientId,
        public string $message,
        public bool $isBulk = false
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->recipientId);

        if (! $user || ! $user->telegram_id) {
            $this->storeMessage(false, 'User or Telegram ID not found');
            return;
        }

        $sender = User::find($this->senderId);
        $senderName = $sender?->name ?? 'Unknown';

        $bot = TelegraphBot::first();

        if (! $bot || ! $bot->token) {
            $this->storeMessage(false, 'Bot configuration not found');
            return;
        }

        $sentSuccessfully = false;
        $errorMsg = null;

        try {
            $messageText = "📨 <b>Xabar</b>\n👤 {$senderName}\n\n" . $this->message;

            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$bot->token}/sendMessage", [
                'chat_id' => $user->telegram_id,
                'text' => $messageText,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                $sentSuccessfully = true;
                Log::info('Message sent via Telegram successfully', [
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                ]);
            } else {
                $errorMsg = "HTTP {$response->status()}: {$response->body()}";
                Log::warning('Failed to send message via Telegram', [
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'response_status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error('Error sending message via Telegram', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->storeMessage($sentSuccessfully, $errorMsg);
    }

    /**
     * Store the message in the database.
     */
    private function storeMessage(bool $sentSuccessfully, ?string $errorMsg): void
    {
        $user = User::find($this->recipientId);

        TelegramMessage::create([
            'sender_id' => $this->senderId,
            'recipient_id' => $this->recipientId,
            'message' => $this->message,
            'telegram_chat_id' => $user?->telegram_id,
            'is_bulk' => $this->isBulk,
            'recipient_count' => $this->isBulk ? null : 1,
            'sent_successfully' => $sentSuccessfully,
            'error_message' => $errorMsg,
        ]);
    }
}
