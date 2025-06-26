<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuthController extends Controller
{
    /**
     * Redirect to social provider
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToProvider($provider)
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Có lỗi xảy ra khi kết nối với ' . ucfirst($provider));
        }
    }

    /**
     * Handle social provider callback
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            // Tìm user đã tồn tại với social ID
            $user = User::where($provider . '_id', $socialUser->getId())->first();

            if ($user) {
                // User đã có, đăng nhập
                Auth::login($user);
                $this->updateLastLogin($user);
                return redirect()->intended('/dashboard')->with('success', 'Đăng nhập thành công với ' . ucfirst($provider));
            }

            // Kiểm tra user có email trùng không
            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // Link social account với user hiện tại
                $existingUser->update([
                    $provider . '_id' => $socialUser->getId(),
                    'provider' => $provider,
                    'avatar' => $socialUser->getAvatar() ?: $existingUser->avatar,
                    'email_verified_at' => now(), // Verify email khi đăng nhập social
                ]);

                Auth::login($existingUser);
                $this->updateLastLogin($existingUser);
                return redirect()->intended('/dashboard')->with('success', 'Tài khoản đã được liên kết với ' . ucfirst($provider));
            }

            // Tạo user mới
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                $provider . '_id' => $socialUser->getId(),
                'provider' => $provider,
                'email_verified_at' => now(),
                'password' => null, // Social users không cần password
                'is_active' => true,
                'subscription_type' => 'trial',
                'subscription_expires_at' => now()->addDays(7), // 7 ngày trial
            ]);

            // Tạo wallet cho user mới
            $user->wallet()->create([
                'balance' => 0,
                'currency' => 'VND',
                'is_active' => true,
            ]);

            Auth::login($user);
            $this->updateLastLogin($user);

            return redirect()->intended('/dashboard')->with('success', 'Tài khoản mới đã được tạo thành công với ' . ucfirst($provider));

        } catch (Exception $e) {
            Log::error('Social login error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Có lỗi xảy ra trong quá trình đăng nhập với ' . ucfirst($provider));
        }
    }

    /**
     * Link social account to existing user
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkAccount(Request $request, $provider)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để liên kết tài khoản');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            $user = Auth::user();

            // Kiểm tra xem social ID đã được sử dụng chưa
            $existingSocialUser = User::where($provider . '_id', $socialUser->getId())
                                    ->where('id', '!=', $user->id)
                                    ->first();

            if ($existingSocialUser) {
                return redirect()->back()->with('error', 'Tài khoản ' . ucfirst($provider) . ' này đã được liên kết với tài khoản khác');
            }

            // Liên kết tài khoản
            $user->update([
                $provider . '_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar() ?: $user->avatar,
            ]);

            return redirect()->back()->with('success', 'Đã liên kết thành công với ' . ucfirst($provider));

        } catch (Exception $e) {
            Log::error('Social link error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi liên kết tài khoản');
        }
    }

    /**
     * Unlink social account
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlinkAccount(Request $request, $provider)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Kiểm tra user có password không (để đảm bảo còn cách đăng nhập)
        if (!$user->password && !$user->google_id && !$user->facebook_id) {
            return redirect()->back()->with('error', 'Bạn phải có ít nhất một phương thức đăng nhập');
        }

        $user->update([
            $provider . '_id' => null,
        ]);

        return redirect()->back()->with('success', 'Đã hủy liên kết với ' . ucfirst($provider));
    }

    /**
     * Update user last login information
     *
     * @param User $user
     * @return void
     */
    private function updateLastLogin(User $user)
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    /**
     * Get available social providers
     *
     * @return array
     */
    public function getProviders()
    {
        return [
            'google' => [
                'name' => 'Google',
                'icon' => 'google',
                'color' => 'bg-red-500 hover:bg-red-600',
            ],
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'facebook',
                'color' => 'bg-blue-600 hover:bg-blue-700',
            ],
        ];
    }
}
