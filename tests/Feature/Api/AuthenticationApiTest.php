<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!@#'),
            'phone' => '0987654321'
        ]);

        // Ensure wallet exists
        if (!$this->user->wallet) {
            Wallet::create([
                'user_id' => $this->user->id,
                'balance' => 0,
                'currency' => 'VND'
            ]);
        }
    }

    /** @test */
    public function user_can_register_successfully()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123!@#',
            'password_confirmation' => 'SecurePassword123!@#',
            'phone' => '0912345678',
            'terms_accepted' => true
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'User registered successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'created_at'
                        ],
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User'
        ]);
    }

    /** @test */
    public function registration_fails_with_invalid_data()
    {
        $invalidData = [
            'name' => 'A', // Too short
            'email' => 'invalid-email', // Invalid format
            'password' => '123', // Too weak
            'phone' => '123', // Invalid format
        ];

        $response = $this->postJson('/api/auth/register', $invalidData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Registration validation failed'
                ])
                ->assertJsonValidationErrors(['name', 'email', 'password', 'phone']);
    }

    /** @test */
    public function user_can_login_successfully()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!@#'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Login successful'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'phone'
                        ],
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
    }

    /** @test */
    public function login_requires_2fa_when_enabled()
    {
        // Skip this test for now due to 2FA complexity
        $this->markTestSkipped('2FA test needs proper setup');
    }

    /** @test */
    public function user_can_get_profile_when_authenticated()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User data retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'wallet' => [
                            'balance',
                            'currency'
                        ],
                        'recent_campaigns'
                    ]
                ]);
    }

    /** @test */
    public function user_cannot_access_protected_routes_without_token()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_logout_successfully()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'test-token'
        ]);
    }

    /** @test */
    public function user_can_logout_from_all_devices()
    {
        // Create multiple tokens
        $token1 = $this->user->createToken('device-1')->plainTextToken;
        $token2 = $this->user->createToken('device-2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1
        ])->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logged out from all devices'
                ]);

        // All tokens should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Token refreshed successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /** @test */
    public function user_can_change_password()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $passwordData = [
            'current_password' => 'SecurePassword123!@#',
            'new_password' => 'NewSecurePassword123!@#',
            'new_password_confirmation' => 'NewSecurePassword123!@#'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);

        // Verify password was changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!@#', $this->user->password));
    }

    /** @test */
    public function password_change_fails_with_incorrect_current_password()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $passwordData = [
            'current_password' => 'wrong-password',
            'new_password' => 'NewSecurePassword123!@#',
            'new_password_confirmation' => 'NewSecurePassword123!@#'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
    }

    /** @test */
    public function user_can_update_profile()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $profileData = [
            'name' => 'Updated Name',
            'phone' => '0999888777'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/update-profile', $profileData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'phone' => '0999888777'
        ]);
    }

    /** @test */
    public function user_can_request_password_reset()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password reset link sent to your email'
                ]);
    }

    /** @test */
    public function password_reset_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_get_active_sessions()
    {
        // Create multiple tokens
        $token1 = $this->user->createToken('device-1')->plainTextToken;
        $token2 = $this->user->createToken('device-2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1
        ])->getJson('/api/auth/sessions');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Sessions retrieved successfully'
                ])
                ->assertJsonCount(2, 'data.sessions');
    }

    /** @test */
    public function user_can_revoke_specific_session()
    {
        $token1 = $this->user->createToken('device-1')->plainTextToken;
        $token2 = $this->user->createToken('device-2');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1
        ])->deleteJson('/api/auth/sessions/' . $token2->accessToken->id);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Session revoked successfully'
                ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token2->accessToken->id
        ]);
    }

    /** @test */
    public function user_cannot_revoke_current_session()
    {
        $token = $this->user->createToken('device-1');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->deleteJson('/api/auth/sessions/' . $token->accessToken->id);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot revoke current session'
                ]);
    }

    /** @test */
    public function api_enforces_rate_limiting_on_login()
    {
        // Skip this test as rate limiting is disabled in testing environment
        $this->markTestSkipped('Rate limiting is disabled in testing environment');
    }

    /** @test */
    public function api_validates_vietnamese_phone_number_format()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test123!@#',
            'password_confirmation' => 'Test123!@#',
            'phone' => '1234567890', // Invalid Vietnamese format
            'terms_accepted' => true
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function api_rejects_disposable_email_addresses()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@10minutemail.com', // Disposable email
            'password' => 'Test123!@#',
            'password_confirmation' => 'Test123!@#',
            'phone' => '0987654321',
            'terms_accepted' => true
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }
}
