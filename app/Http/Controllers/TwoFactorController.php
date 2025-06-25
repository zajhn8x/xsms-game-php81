<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorSetupRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Cache;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->middleware('auth');
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show 2FA status and settings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $data = [
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'phone_verified' => $user->hasVerifiedPhone(),
            'available_methods' => $this->twoFactorService->getAvailableMethods($user),
            'recovery_codes_count' => count($user->two_factor_recovery_codes ?? [])
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('auth.two-factor.index', $data);
    }

    /**
     * Enable TOTP 2FA
     */
    public function enableTotp(Request $request): JsonResponse
    {
        try {
            $result = $this->twoFactorService->enableTotp($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Quét mã QR hoặc nhập key thủ công vào ứng dụng Authenticator',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Confirm TOTP setup
     */
    public function confirmTotp(TwoFactorSetupRequest $request): JsonResponse
    {

        try {
            $success = $this->twoFactorService->confirmTotp(
                $request->user(),
                $request->code
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '2FA đã được kích hoạt thành công',
                    'recovery_codes' => $request->user()->two_factor_recovery_codes
                ]);
            }

            throw new Exception('Mã xác thực không chính xác');

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Disable 2FA
     */
    public function disable(TwoFactorSetupRequest $request): JsonResponse
    {

        try {
            $success = $this->twoFactorService->disable(
                $request->user(),
                $request->password
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '2FA đã được tắt'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể tắt 2FA. Vui lòng thử lại.'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send SMS token
     */
    public function sendSmsToken(Request $request): JsonResponse
    {
        try {
            $this->twoFactorService->sendSmsToken($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Mã xác thực đã được gửi qua SMS'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send email token
     */
    public function sendEmailToken(Request $request): JsonResponse
    {
        try {
            $this->twoFactorService->sendEmailToken($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Mã xác thực đã được gửi qua email'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate new recovery codes
     */
    public function generateRecoveryCodes(TwoFactorSetupRequest $request): JsonResponse
    {

        if (!password_verify($request->password, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu không chính xác'
            ], 400);
        }

        $codes = $request->user()->generateTwoFactorRecoveryCodes();

        return response()->json([
            'success' => true,
            'message' => 'Mã khôi phục mới đã được tạo',
            'recovery_codes' => $codes
        ]);
    }

    /**
     * Show 2FA challenge form
     */
    public function showChallenge(Request $request)
    {
        $user = $request->user();

        if (!$this->twoFactorService->needsTwoFactorChallenge($user)) {
            return redirect()->intended('/dashboard');
        }

        $availableMethods = $this->twoFactorService->getAvailableMethods($user);

        if ($request->expectsJson()) {
            return response()->json([
                'requires_two_factor' => true,
                'available_methods' => $availableMethods
            ]);
        }

        return view('auth.two-factor.challenge', [
            'available_methods' => $availableMethods
        ]);
    }

    /**
     * Verify 2FA challenge
     */
    public function verifyChallenge(TwoFactorSetupRequest $request): JsonResponse
    {

        $user = $request->user();
        $code = $request->code;
        $method = $request->method;

        try {
            $verified = false;

            switch ($method) {
                case 'totp':
                    $verified = $this->twoFactorService->verifyUserTotpCode($user, $code);
                    break;
                case 'sms':
                    $verified = $this->twoFactorService->verifySmsToken($user, $code);
                    break;
                case 'email':
                    $verified = $this->twoFactorService->verifyEmailToken($user, $code);
                    break;
                case 'recovery':
                    $verified = $this->twoFactorService->verifyRecoveryCode($user, $code);
                    break;
            }

            if ($verified) {
                // Mark session as 2FA verified
                session(['two_factor_verified' => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'Xác thực thành công',
                    'redirect' => $request->session()->get('intended', '/dashboard')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Mã xác thực không chính xác hoặc đã hết hạn'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Trả về ảnh QR code từ otpauth url (dạng PNG)
     */
    public function qrImage(Request $request)
    {
        $user = $request->user();
        $secret = Cache::get("2fa_temp_secret_{$user->id}");
        if (!$secret) {
            return response('Không tìm thấy secret', 404);
        }
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);
        return response($qrCodeSvg)->header('Content-Type', 'image/svg+xml');
    }
}
