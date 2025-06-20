<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TwoFactorToken;
use App\Models\ActivityLog;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected TwoFactorService $twoFactorService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twoFactorService = app(TwoFactorService::class);
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
    }

    public function test_user_can_access_two_factor_settings_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/two-factor');

        $response->assertStatus(200);
        $response->assertViewIs('auth.two-factor.index');
        $response->assertViewHas('two_factor_enabled', false);
    }

    public function test_user_can_enable_totp_2fa(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/two-factor/enable-totp');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('secret', $responseData['data']);
        $this->assertArrayHasKey('qr_code_url', $responseData['data']);
    }

    public function test_user_can_confirm_totp_setup(): void
    {
        // First enable TOTP
        $setupResult = $this->twoFactorService->enableTotp($this->user);
        $secret = $setupResult['secret'];

        // Generate a valid TOTP code
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->user)
            ->postJson('/two-factor/confirm-totp', [
                'code' => $validCode
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        // Check user is updated
        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertNotNull($this->user->two_factor_recovery_codes);
    }

    public function test_user_cannot_confirm_totp_with_invalid_code(): void
    {
        // First enable TOTP
        $this->twoFactorService->enableTotp($this->user);

        $response = $this->actingAs($this->user)
            ->postJson('/two-factor/confirm-totp', [
                'code' => '123456' // Invalid code
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
    }

    public function test_user_can_disable_2fa_with_correct_password(): void
    {
        // First enable 2FA
        $this->enable2FAForUser();

        $response = $this->actingAs($this->user)
            ->deleteJson('/two-factor/disable', [
                'password' => 'password123'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        // Check user is updated
        $this->user->refresh();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
        $this->assertNull($this->user->two_factor_secret);
    }

    public function test_user_cannot_disable_2fa_with_wrong_password(): void
    {
        // First enable 2FA
        $this->enable2FAForUser();

        $response = $this->actingAs($this->user)
            ->deleteJson('/two-factor/disable', [
                'password' => 'wrongpassword'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);

        // Check 2FA is still enabled
        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
    }

    public function test_sms_token_generation(): void
    {
        // Set verified phone
        $this->user->update([
            'phone_number' => '+1234567890',
            'phone_verified_at' => now()
        ]);

        $token = $this->twoFactorService->sendSmsToken($this->user);

        $this->assertInstanceOf(TwoFactorToken::class, $token);
        $this->assertEquals($this->user->id, $token->user_id);
        $this->assertEquals('sms', $token->type);
        $this->assertTrue($token->isValid());
    }

    public function test_sms_token_verification(): void
    {
        // Set verified phone
        $this->user->update([
            'phone_number' => '+1234567890',
            'phone_verified_at' => now()
        ]);

        $token = TwoFactorToken::generateSmsToken($this->user);

        // Test valid token
        $isValid = $this->twoFactorService->verifySmsToken($this->user, $token->token);
        $this->assertTrue($isValid);

        // Test invalid token
        $isInvalid = $this->twoFactorService->verifySmsToken($this->user, '999999');
        $this->assertFalse($isInvalid);
    }

    public function test_email_token_generation_and_verification(): void
    {
        $token = $this->twoFactorService->sendEmailToken($this->user);

        $this->assertInstanceOf(TwoFactorToken::class, $token);
        $this->assertEquals('email', $token->type);

        // Test verification
        $isValid = $this->twoFactorService->verifyEmailToken($this->user, $token->token);
        $this->assertTrue($isValid);

        // Token should be marked as used now
        $token->refresh();
        $this->assertNotNull($token->used_at);
    }

    public function test_recovery_codes_generation(): void
    {
        $codes = $this->user->generateTwoFactorRecoveryCodes();

        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);

        // Each code should be 10 characters
        foreach ($codes as $code) {
            $this->assertEquals(10, strlen($code));
        }

        // Check user has codes
        $this->user->refresh();
        $this->assertEquals($codes, $this->user->two_factor_recovery_codes);
    }

    public function test_recovery_code_usage(): void
    {
        $codes = $this->user->generateTwoFactorRecoveryCodes();
        $firstCode = $codes[0];

        // Use recovery code
        $isValid = $this->user->useRecoveryCode($firstCode);
        $this->assertTrue($isValid);

        // Check code is removed
        $this->user->refresh();
        $this->assertNotContains($firstCode, $this->user->two_factor_recovery_codes);
        $this->assertCount(7, $this->user->two_factor_recovery_codes);

        // Cannot use same code again
        $isInvalid = $this->user->useRecoveryCode($firstCode);
        $this->assertFalse($isInvalid);
    }

    public function test_activity_logging_for_2fa_actions(): void
    {
        // Enable 2FA
        $setupResult = $this->twoFactorService->enableTotp($this->user);
        $secret = $setupResult['secret'];

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($this->user)
            ->postJson('/two-factor/confirm-totp', [
                'code' => $validCode
            ]);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => '2fa_action'
        ]);
    }

    public function test_rate_limiting_for_sms_tokens(): void
    {
        // Set verified phone
        $this->user->update([
            'phone_number' => '+1234567890',
            'phone_verified_at' => now()
        ]);

        // Generate 5 tokens (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->twoFactorService->sendSmsToken($this->user);
        }

        // 6th attempt should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bạn đã yêu cầu quá nhiều mã SMS');

        $this->twoFactorService->sendSmsToken($this->user);
    }

    public function test_cleanup_expired_tokens(): void
    {
        // Create some expired tokens
        TwoFactorToken::create([
            'user_id' => $this->user->id,
            'token' => '123456',
            'type' => 'sms',
            'expires_at' => now()->subHours(2),
            'created_at' => now()->subHours(2)
        ]);

        TwoFactorToken::create([
            'user_id' => $this->user->id,
            'token' => '789012',
            'type' => 'email',
            'expires_at' => now()->addHour(),
            'created_at' => now()
        ]);

        $deletedCount = TwoFactorToken::cleanExpired();

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, TwoFactorToken::count());
    }

    public function test_cleanup_old_activity_logs(): void
    {
        // Create some old logs
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'test_action',
            'description' => 'Test description',
            'created_at' => now()->subMonths(7)
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'recent_action',
            'description' => 'Recent description',
            'created_at' => now()->subDays(30)
        ]);

        $deletedCount = ActivityLog::cleanOldLogs();

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, ActivityLog::count());
    }

    /**
     * Helper method to enable 2FA for user
     */
    private function enable2FAForUser(): void
    {
        $setupResult = $this->twoFactorService->enableTotp($this->user);
        $secret = $setupResult['secret'];

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->twoFactorService->confirmTotp($this->user, $validCode);
    }
}
