<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'region',
        'telegram_id',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Find or create user by phone number
     */
    public static function findOrCreateByPhone(string $phoneNumber, string $name = null): self
    {
        return static::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'name' => $name ?? 'User',
                'email' => $phoneNumber . '@telegram.local', // Temporary email for Telegram users
                'password' => bcrypt(Str::random(16)), // Random password for Telegram users
                'role' => 'user',
            ]
        );
    }

    /**
     * Find or create user by Telegram ID
     */
    public static function findOrCreateByTelegramId(string $telegramId, string $name = null): self
    {
        return static::firstOrCreate(
            ['telegram_id' => $telegramId],
            [
                'name' => $name ?? 'User',
                'email' => $telegramId . '@telegram.local', // Temporary email for Telegram users
                'password' => bcrypt(Str::random(16)), // Random password for Telegram users
                'role' => 'user',
            ]
        );
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is a checker
     */
    public function isChecker(): bool
    {
        return $this->hasRole('checker');
    }

    /**
     * Check if user is a registrator
     */
    public function isRegistrator(): bool
    {
        return $this->hasRole('registrator');
    }

    /**
     * Check if user is a CEO
     */
    public function isCeo(): bool
    {
        return $this->hasRole('ceo');
    }

    /**
     * Get uploaded files for this user
     */
    public function uploadedFiles()
    {
        return $this->hasMany(UploadedFile::class);
    }
}
