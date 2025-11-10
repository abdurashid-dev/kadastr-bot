<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'message',
        'telegram_chat_id',
        'is_bulk',
        'recipient_count',
        'sent_successfully',
        'error_message',
    ];

    protected $casts = [
        'is_bulk' => 'boolean',
        'sent_successfully' => 'boolean',
        'recipient_count' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
