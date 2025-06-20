<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TwoFactorToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'used_at',
        'ip_address'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new SMS token for user
     */
    public static function generateSmsToken(User $user): self
    {
        // Expire any existing tokens
        self::where('user_id', $user->id)
            ->where('type', 'sms')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return self::create([
            'user_id' => $user->id,
            'token' => Str::padLeft(random_int(0, 999999), 6, '0'),
            'type' => 'sms',
            'expires_at' => now()->addMinutes(5),
            'ip_address' => request()->ip()
        ]);
    }

    /**
     * Generate a new email token for user
     */
    public static function generateEmailToken(User $user): self
    {
        // Expire any existing tokens
        self::where('user_id', $user->id)
            ->where('type', 'email')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return self::create([
            'user_id' => $user->id,
            'token' => Str::padLeft(random_int(0, 999999), 6, '0'),
            'type' => 'email',
            'expires_at' => now()->addMinutes(10),
            'ip_address' => request()->ip()
        ]);
    }

    /**
     * Check if token is valid and not expired
     */
    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Verify token for user
     */
    public static function verify(User $user, string $token, string $type = 'sms'): bool
    {
        $tokenRecord = self::where('user_id', $user->id)
            ->where('token', $token)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($tokenRecord) {
            $tokenRecord->markAsUsed();
            return true;
        }

        return false;
    }

    /**
     * Clean expired tokens
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now()->subHours(24))->delete();
    }
}
