<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedFile extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'name',
        'original_filename',
        'telegram_file_id',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'status',
        'admin_notes',
        'registered_count',
        'not_registered_count',
        'accepted_note',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'registered_count' => 'integer',
        'not_registered_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending files (for checkers)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for waiting files (for registrators)
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * Scope for accepted files
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope for rejected files
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if file can be approved by checker
     */
    public function canBeApprovedByChecker(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if file can be approved by registrator
     */
    public function canBeApprovedByRegistrator(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Approve file by checker
     */
    public function approveByChecker(): bool
    {
        if (! $this->canBeApprovedByChecker()) {
            return false;
        }

        $this->status = self::STATUS_WAITING;

        return $this->save();
    }

    /**
     * Approve file by registrator
     */
    public function approveByRegistrator(): bool
    {
        if (! $this->canBeApprovedByRegistrator()) {
            return false;
        }

        $this->status = self::STATUS_ACCEPTED;

        return $this->save();
    }

    /**
     * Reject file
     */
    public function reject(?string $notes = null): bool
    {
        if ($notes) {
            $this->admin_notes = $notes;
        }

        $this->status = self::STATUS_REJECTED;

        return $this->save();
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_WAITING => 'bg-blue-100 text-blue-800',
            self::STATUS_ACCEPTED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
