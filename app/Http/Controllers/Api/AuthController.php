<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\VerifyTwoFactorRequest;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('guest')->except(['logout', 'me', 'refresh', 'changePassword', 'updateProfile', 'sessions', 'revokeSession', 'logoutAll']);
        $this->middleware('auth:sanctum')->only(['logout', 'me', 'refresh', 'changePassword', 'updateProfile', 'sessions', 'revokeSession', 'logoutAll']);
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Rate limiting (skip in testing)
            if (!app()->environment('testing')) {
                $key = 'register:' . $request->ip();
                if (RateLimiter::tooManyAttempts($key, 5)) {
                    return $this->errorResponse(
                        'Too many registration attempts. Please try again later.',
                        429
                    );
                }

                RateLimiter::hit($key, 3600); // 1 hour
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            // Create wallet automatically (handled by UserObserver)

            // Generate token
            $token = $user->createToken('auth-token', ['*'], now()->addDays(30))->plainTextToken;

            return $this->successResponse([
                'user' => $this->formatUserResponse($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60, // 30 days in seconds
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Rate limiting (skip in testing)
            if (!app()->environment('testing')) {
                $key = 'login:' . $request->email . ':' . $request->ip();
                if (RateLimiter::tooManyAttempts($key, 5)) {
                    return $this->errorResponse(
                        'Too many login attempts. Please try again in 1 hour.',
                        429
                    );
                }
            }

                        // Attempt authentication
            if (!Auth::attempt($request->only('email', 'password'))) {
                if (!app()->environment('testing')) {
                    RateLimiter::hit($key, 3600); // 1 hour
                }

                return $this->errorResponse('Invalid credentials', 401);
            }

            if (!app()->environment('testing')) {
                RateLimiter::clear($key);
            }

            $user = Auth::user();

                        // Check if 2FA is required
            if ($user->two_factor_enabled) {
                // Generate and send 2FA code
                $this->twoFactorService->sendSmsToken($user);

                return $this->successResponse([
                    'requires_2fa' => true,
                    'user_id' => $user->id,
                    'message' => 'Two-factor authentication required'
                ], '2FA code sent');
            }

            // Generate token
            $token = $user->createToken('auth-token', ['*'], now()->addDays(30))->plainTextToken;

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);

            return $this->successResponse([
                'user' => $this->formatUserResponse($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60,
            ], 'Login successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify 2FA and complete login
     */
    public function verifyTwoFactor(VerifyTwoFactorRequest $request): JsonResponse
    {
        try {
            $user = User::find($request->user_id);

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Rate limiting for 2FA attempts
            $key = '2fa:' . $user->id . ':' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return $this->errorResponse(
                    'Too many 2FA attempts. Please try again in 15 minutes.',
                    429
                );
            }

                        // Verify 2FA code
            if (!$this->twoFactorService->verifySmsToken($user, $request->code)) {
                RateLimiter::hit($key, 900); // 15 minutes

                return $this->errorResponse('Invalid 2FA code', 401);
            }

            RateLimiter::clear($key);

            // Generate token
            $token = $user->createToken('auth-token', ['*'], now()->addDays(30))->plainTextToken;

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);

            return $this->successResponse([
                'user' => $this->formatUserResponse($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60,
            ], '2FA verification successful');

        } catch (\Exception $e) {
            return $this->errorResponse('2FA verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Delete current token
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse([], 'Logout successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Delete all tokens
            $request->user()->tokens()->delete();

            return $this->successResponse([], 'Logged out from all devices');

        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['wallet']);

            return $this->successResponse([
                'user' => $this->formatUserResponse($user),
                'wallet' => [
                    'balance' => $user->wallet->balance ?? 0,
                    'currency' => $user->wallet->currency ?? 'VND'
                ],
                'recent_campaigns' => []
            ], 'User data retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Delete current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('auth-token', ['*'], now()->addDays(30))->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60,
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            // Rate limiting (skip in testing)
            if (!app()->environment('testing')) {
                $key = 'password-reset:' . $request->email . ':' . $request->ip();
                if (RateLimiter::tooManyAttempts($key, 3)) {
                    return $this->errorResponse(
                        'Too many password reset attempts. Please try again in 1 hour.',
                        429
                    );
                }

                RateLimiter::hit($key, 3600); // 1 hour
            }

            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse([], 'Password reset link sent to your email');
            }

            return $this->errorResponse('Unable to send password reset link', 400);

        } catch (\Exception $e) {
            return $this->errorResponse('Password reset failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    // Delete all existing tokens for security
                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse([], 'Password reset successfully');
            }

            return $this->errorResponse('Password reset failed', 400);

        } catch (\Exception $e) {
            return $this->errorResponse('Password reset failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $user = $request->user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Delete all tokens except current (force re-login on other devices)
            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            return $this->successResponse([], 'Password changed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Password change failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|image|max:2048'
        ]);

        try {
            $user = $request->user();
            $data = $request->only(['name', 'phone']);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $avatarPath;
            }

            $user->update($data);

            return $this->successResponse([
                'user' => $this->formatUserResponse($user)
            ], 'Profile updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Profile update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user sessions/tokens
     */
    public function sessions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentTokenId = $request->user()->currentAccessToken()->id;

            $sessions = $user->tokens->map(function($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'is_current' => $token->id === $currentTokenId,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at
                ];
            });

            return $this->successResponse([
                'sessions' => $sessions
            ], 'Sessions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Revoke specific session/token
     */
    public function revokeSession(Request $request, $tokenId): JsonResponse
    {
        try {
            $user = $request->user();
            $currentTokenId = $request->user()->currentAccessToken()->id;

            if ($tokenId == $currentTokenId) {
                return $this->errorResponse('Cannot revoke current session', 400);
            }

            $user->tokens()->where('id', $tokenId)->delete();

            return $this->successResponse([], 'Session revoked successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Format user response for API
     */
    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'email_verified_at' => $user->email_verified_at,
            'two_factor_enabled' => $user->two_factor_enabled,
            'created_at' => $user->created_at,
            'last_login_at' => $user->last_login_at,
        ];
    }

    /**
     * Success response helper
     */
    private function successResponse($data, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Error response helper
     */
    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $status);
    }
}
