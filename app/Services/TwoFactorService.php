<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorToken;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Exception;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = app(Google2FA::class);
    }

    /**
     * Enable TOTP 2FA for user
     */
    public function enableTotp(User $user): array
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new Exception('2FA đã được kích hoạt cho tài khoản này');
        }

        $secret = $this->google2fa->generateSecretKey();

        // Store secret temporarily (not confirmed yet)
        Cache::put("2fa_temp_secret_{$user->id}", $secret, now()->addMinutes(10));

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'manual_entry_key' => $secret
        ];
    }

    /**
     * Confirm TOTP setup with verification code
     */
    public function confirmTotp(User $user, string $code): bool
    {
        $secret = Cache::get("2fa_temp_secret_{$user->id}");

        if (!$secret) {
            throw new Exception('Phiên thiết lập 2FA đã hết hạn. Vui lòng thử lại.');
        }

        if ($this->verifyTotpCode($secret, $code)) {
            $user->update([
                'two_factor_secret' => encrypt($secret),
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now()
            ]);

            // Generate recovery codes
            $user->generateTwoFactorRecoveryCodes();

            // Clear temporary secret
            Cache::forget("2fa_temp_secret_{$user->id}");

            // Log activity
            Log::info('2FA enabled for user', ['user_id' => $user->id]);

            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user, string $password): bool
    {
        if (!password_verify($password, $user->password)) {
            throw new Exception('Mật khẩu không chính xác');
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null
        ]);

        // Expire all existing tokens
        $user->twoFactorTokens()->whereNull('used_at')->update(['used_at' => now()]);

        Log::info('2FA disabled for user', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Verify TOTP code
     */
    public function verifyTotpCode(string $secret, string $code): bool
    {
        if (is_encrypted($secret)) {
            $secret = decrypt($secret);
        }

        return $this->google2fa->verifyKey($secret, $code, 2); // 2 windows tolerance
    }

    /**
     * Verify user's TOTP code
     */
    public function verifyUserTotpCode(User $user, string $code): bool
    {
        if (!$user->hasTwoFactorEnabled()) {
            return false;
        }

        return $this->verifyTotpCode($user->two_factor_secret, $code);
    }

    /**
     * Send SMS token to user
     */
    public function sendSmsToken(User $user): TwoFactorToken
    {
        if (!$user->hasVerifiedPhone()) {
            throw new Exception('Số điện thoại chưa được xác thực');
        }

        // Rate limiting: max 5 SMS per hour (skip in testing)
        if (!app()->environment('testing')) {
            $recentTokens = $user->twoFactorTokens()
                ->where('type', 'sms')
                ->where('created_at', '>', now()->subHour())
                ->count();

            if ($recentTokens >= 5) {
                throw new Exception('Bạn đã yêu cầu quá nhiều mã SMS. Vui lòng thử lại sau 1 giờ.');
            }
        }

        $token = TwoFactorToken::generateSmsToken($user);

        // TODO: Integrate with SMS service (Twilio, AWS SNS, etc.)
        // For now, we'll just log it
        Log::info('SMS 2FA token generated', [
            'user_id' => $user->id,
            'token' => $token->token,
            'phone' => $user->phone_number
        ]);

        // In production, send actual SMS:
        // $this->sendSms($user->phone_number, "Mã xác thực của bạn là: {$token->token}");

        return $token;
    }

    /**
     * Send email token to user
     */
    public function sendEmailToken(User $user): TwoFactorToken
    {
        // Rate limiting: max 5 emails per hour
        $recentTokens = $user->twoFactorTokens()
            ->where('type', 'email')
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentTokens >= 5) {
            throw new Exception('Bạn đã yêu cầu quá nhiều mã email. Vui lòng thử lại sau 1 giờ.');
        }

        $token = TwoFactorToken::generateEmailToken($user);

        // TODO: Send email notification
        // Notification::send($user, new TwoFactorEmailNotification($token));

        Log::info('Email 2FA token generated', [
            'user_id' => $user->id,
            'token' => $token->token,
            'email' => $user->email
        ]);

        return $token;
    }

    /**
     * Verify SMS token
     */
    public function verifySmsToken(User $user, string $code): bool
    {
        return TwoFactorToken::verify($user, $code, 'sms');
    }

    /**
     * Verify email token
     */
    public function verifyEmailToken(User $user, string $code): bool
    {
        return TwoFactorToken::verify($user, $code, 'email');
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if ($user->useRecoveryCode($code)) {
            Log::info('Recovery code used', [
                'user_id' => $user->id,
                'remaining_codes' => count($user->two_factor_recovery_codes ?? [])
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if user needs 2FA challenge
     */
    public function needsTwoFactorChallenge(User $user): bool
    {
        return $user->hasTwoFactorEnabled() || $user->requiresTwoFactor();
    }

    /**
     * Get available 2FA methods for user
     */
    public function getAvailableMethods(User $user): array
    {
        $methods = [];

        if ($user->hasTwoFactorEnabled()) {
            $methods[] = 'totp';
        }

        if ($user->hasVerifiedPhone()) {
            $methods[] = 'sms';
        }

        $methods[] = 'email';

        if ($user->two_factor_recovery_codes && count($user->two_factor_recovery_codes) > 0) {
            $methods[] = 'recovery';
        }

        return $methods;
    }

    /**
     * Clean expired tokens (should be run via scheduled command)
     */
    public function cleanExpiredTokens(): int
    {
        return TwoFactorToken::cleanExpired();
    }
}

/**
 * Helper function to check if string is encrypted
 */
function is_encrypted($value): bool
{
    if (!is_string($value)) {
        return false;
    }

    $payload = json_decode(base64_decode($value), true);

    return $payload && isset($payload['iv']) && isset($payload['value']) && isset($payload['mac']);
}
