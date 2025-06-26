<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar',
        'subscription_type', 'subscription_expires_at',
        'balance', 'total_deposit', 'total_withdrawal',
        'is_active', 'last_login_at', 'last_login_ip', 'phone_number',
        'two_factor_enabled', 'google_id', 'facebook_id', 'provider'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'subscription_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance' => 'decimal:2',
        'total_deposit' => 'decimal:2',
        'total_withdrawal' => 'decimal:2',
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'two_factor_recovery_codes' => 'array'
    ];

    // Relationships
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function historicalCampaigns(): HasMany
    {
        return $this->hasMany(HistoricalCampaign::class);
    }

    public function riskRules(): HasMany
    {
        return $this->hasMany(RiskManagementRule::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(SocialFollow::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(SocialFollow::class, 'follower_id');
    }

    public function sharedCampaigns(): HasMany
    {
        return $this->hasMany(CampaignShare::class, 'shared_by_user_id');
    }

    public function twoFactorTokens(): HasMany
    {
        return $this->hasMany(TwoFactorToken::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Helper methods for subscription
    public function isPremium(): bool
    {
        return $this->subscription_type === 'premium'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    public function isBasic(): bool
    {
        return $this->subscription_type === 'basic';
    }

    public function isTrial(): bool
    {
        return $this->subscription_type === 'trial'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    public function getActiveCampaignsCountAttribute(): int
    {
        return $this->campaigns()->whereIn('status', ['active', 'running'])->count();
    }

    public function getTotalProfitAttribute(): float
    {
        return $this->campaigns()->sum(\Illuminate\Support\Facades\DB::raw('current_balance - initial_balance'));
    }

    // Social methods
    public function follow($userId): bool
    {
        if ($this->id === $userId) {
            return false; // Can't follow yourself
        }

        return $this->following()->firstOrCreate(['following_id' => $userId]) !== null;
    }

    public function unfollow($userId): bool
    {
        return $this->following()->where('following_id', $userId)->delete() > 0;
    }

    public function isFollowing($userId): bool
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    public function getFollowingCountAttribute(): int
    {
        return $this->following()->count();
    }

    // Two-Factor Authentication methods

    /**
     * Check if user has 2FA enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    /**
     * Check if 2FA is required for this user
     */
    public function requiresTwoFactor(): bool
    {
        // Require 2FA for users with high balance or VIP status
        return $this->hasTwoFactorEnabled() ||
               ($this->isPremium() && $this->balance > 10000000) ||
               in_array($this->subscription_type, ['admin', 'vip']);
    }

    /**
     * Generate recovery codes for 2FA
     */
    public function generateTwoFactorRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10));
        }

        $this->two_factor_recovery_codes = $codes;
        $this->save();

        return $codes;
    }

    /**
     * Check if recovery code is valid and use it
     */
    public function useRecoveryCode(string $code): bool
    {
        if (!$this->two_factor_recovery_codes) {
            return false;
        }

        $codes = $this->two_factor_recovery_codes;
        $key = array_search(strtoupper($code), $codes);

        if ($key !== false) {
            unset($codes[$key]);
            $this->two_factor_recovery_codes = array_values($codes);
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Check if phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return !empty($this->phone_number) && $this->phone_verified_at !== null;
    }
}
