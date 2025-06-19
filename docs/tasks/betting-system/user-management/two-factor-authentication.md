# Xác thực 2 Yếu tố (Two-Factor Authentication)

## Tổng quan
Implement hệ thống xác thực 2 yếu tố (2FA) để tăng cường bảo mật cho tài khoản người dùng, đặc biệt quan trọng cho hệ thống betting với giao dịch tài chính.

## Mục tiêu
- Tăng cường bảo mật tài khoản
- Hỗ trợ TOTP (Time-based One-Time Password)
- SMS backup authentication
- Recovery codes cho trường hợp mất thiết bị
- Tùy chọn bắt buộc 2FA cho high-value accounts

## Technical Requirements

### 1. TOTP Authentication
- Google Authenticator compatibility
- QR code generation
- Backup codes generation
- Time-based validation

### 2. SMS Backup
- SMS OTP as secondary method
- Phone number verification
- Rate limiting for SMS sends
- International phone support

### 3. Recovery System
- One-time recovery codes
- Admin emergency access
- Account recovery process
- Security audit trail

## Implementation Steps

### Step 1: Database Schema
```sql
-- Migration: add_two_factor_columns_to_users_table
ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN two_factor_recovery_codes TEXT NULL;
ALTER TABLE users ADD COLUMN two_factor_confirmed_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN phone_verified_at TIMESTAMP NULL;

-- Create two_factor_tokens table
CREATE TABLE two_factor_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token VARCHAR(6) NOT NULL,
    type ENUM('sms', 'email') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, token),
    INDEX idx_expires_at (expires_at)
);
```

### Step 2: Update User Model
```php
// app/Models/User.php
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 
        'phone_verified_at', 'two_factor_confirmed_at'
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'encrypted:array'
    ];

    // Check if user has 2FA enabled
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    // Check if 2FA is required for this user
    public function requiresTwoFactor(): bool
    {
        // Require 2FA for users with high balance or VIP status
        return $this->hasTwoFactorEnabled() || 
               $this->hasRole('vip') ||
               ($this->wallet && $this->wallet->total_balance > 10000000); // 10M VND
    }

    // Generate new recovery codes
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtolower(Str::random(10));
        }
        
        $this->forceFill([
            'two_factor_recovery_codes' => $codes
        ])->save();

        return $codes;
    }

    // Use a recovery code
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        
        foreach ($codes as $index => $recoveryCode) {
            if (hash_equals($recoveryCode, $code)) {
                unset($codes[$index]);
                $this->forceFill([
                    'two_factor_recovery_codes' => array_values($codes)
                ])->save();
                
                return true;
            }
        }
        
        return false;
    }

    // SMS tokens relationship
    public function smsTokens()
    {
        return $this->hasMany(TwoFactorToken::class);
    }
}
```

### Step 3: TwoFactorToken Model
```php
// app/Models/TwoFactorToken.php
class TwoFactorToken extends Model
{
    protected $fillable = [
        'user_id', 'token', 'type', 'expires_at', 'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function markAsUsed(): self
    {
        $this->update(['used_at' => now()]);
        return $this;
    }

    // Generate SMS token
    public static function generateSmsToken(User $user): self
    {
        // Delete old unused tokens
        $user->smsTokens()->where('type', 'sms')
             ->whereNull('used_at')
             ->delete();

        return self::create([
            'user_id' => $user->id,
            'token' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'type' => 'sms',
            'expires_at' => now()->addMinutes(5)
        ]);
    }
}
```

### Step 4: Two Factor Service
```php
// app/Services/TwoFactorService.php
class TwoFactorService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // Enable TOTP for user
    public function enableTotp(User $user): array
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new \Exception('2FA đã được kích hoạt');
        }

        // Generate secret
        $secret = $this->generateSecret();
        
        // Store secret temporarily (not confirmed yet)
        $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();

        // Generate QR code
        $qrCodeUrl = $this->generateQrCode($user, $secret);

        return [
            'secret' => $secret,
            'qr_code' => $qrCodeUrl,
            'backup_codes' => $user->generateRecoveryCodes()
        ];
    }

    // Confirm TOTP setup
    public function confirmTotp(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);
        
        if (!$this->verifyTotpCode($secret, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        // Log security event
        activity()
            ->causedBy($user)
            ->log('Two-factor authentication enabled');

        return true;
    }

    // Disable 2FA
    public function disable(User $user, string $password): bool
    {
        if (!Hash::check($password, $user->password)) {
            throw new \Exception('Mật khẩu không chính xác');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null
        ])->save();

        // Log security event
        activity()
            ->causedBy($user)
            ->log('Two-factor authentication disabled');

        return true;
    }

    // Verify TOTP code
    public function verifyTotpCode(string $secret, string $code): bool
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        
        return $google2fa->verifyKey($secret, $code, 2); // 2 windows tolerance
    }

    // Send SMS token
    public function sendSmsToken(User $user): bool
    {
        if (!$user->phone || !$user->phone_verified_at) {
            throw new \Exception('Số điện thoại chưa được xác thực');
        }

        // Rate limiting
        $recentTokens = $user->smsTokens()
            ->where('type', 'sms')
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();

        if ($recentTokens >= 3) {
            throw new \Exception('Bạn đã gửi quá nhiều mã xác thực. Vui lòng thử lại sau 5 phút.');
        }

        $token = TwoFactorToken::generateSmsToken($user);
        
        return $this->smsService->send(
            $user->phone,
            "Mã xác thực 2FA của bạn là: {$token->token}. Có hiệu lực trong 5 phút."
        );
    }

    // Verify SMS token
    public function verifySmsToken(User $user, string $code): bool
    {
        $token = $user->smsTokens()
            ->where('type', 'sms')
            ->where('token', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            return false;
        }

        $token->markAsUsed();
        return true;
    }

    // Generate secret key
    protected function generateSecret(): string
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        return $google2fa->generateSecretKey();
    }

    // Generate QR code URL
    protected function generateQrCode(User $user, string $secret): string
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        
        return $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }
}
```

### Step 5: Controllers
```php
// app/Http/Controllers/TwoFactorController.php
class TwoFactorController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('auth');
        $this->middleware('password.confirm')->only(['enable', 'disable']);
    }

    public function index()
    {
        $user = auth()->user();
        
        return view('auth.two-factor.index', [
            'user' => $user,
            'recoveryCodes' => $user->two_factor_recovery_codes ?? []
        ]);
    }

    public function enable(Request $request)
    {
        try {
            $result = $this->twoFactorService->enableTotp(auth()->user());
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        try {
            $success = $this->twoFactorService->confirmTotp(
                auth()->user(),
                $request->code
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '2FA đã được kích hoạt thành công'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Mã xác thực không chính xác'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        try {
            $this->twoFactorService->disable(
                auth()->user(),
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => '2FA đã được tắt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function regenerateRecoveryCodes()
    {
        $codes = auth()->user()->generateRecoveryCodes();
        
        return response()->json([
            'success' => true,
            'codes' => $codes
        ]);
    }

    public function sendSms()
    {
        try {
            $this->twoFactorService->sendSmsToken(auth()->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Mã xác thực đã được gửi qua SMS'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

### Step 6: Authentication Challenge
```php
// app/Http/Controllers/Auth/TwoFactorChallengeController.php
class TwoFactorChallengeController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('guest');
    }

    public function create(Request $request)
    {
        if (!$request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'recovery_code' => 'nullable|string'
        ]);

        $userId = $request->session()->get('two_factor.user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $authenticated = false;

        // Try recovery code first
        if ($request->recovery_code) {
            $authenticated = $user->useRecoveryCode($request->recovery_code);
            
            if ($authenticated) {
                activity()
                    ->causedBy($user)
                    ->log('Two-factor authentication using recovery code');
            }
        }
        // Try TOTP code
        elseif (strlen($request->code) === 6 && is_numeric($request->code)) {
            if ($user->hasTwoFactorEnabled()) {
                $secret = decrypt($user->two_factor_secret);
                $authenticated = $this->twoFactorService->verifyTotpCode($secret, $request->code);
            }
            
            // Fallback to SMS if TOTP fails
            if (!$authenticated) {
                $authenticated = $this->twoFactorService->verifySmsToken($user, $request->code);
            }
        }

        if ($authenticated) {
            Auth::login($user, $request->session()->get('two_factor.remember_me', false));
            
            $request->session()->forget(['two_factor.user_id', 'two_factor.remember_me']);
            $request->session()->regenerate();

            // Update last login
            $user->update(['last_login_at' => now()]);

            activity()
                ->causedBy($user)
                ->log('Two-factor authentication successful');

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'code' => 'Mã xác thực không chính xác'
        ]);
    }
}
```

### Step 7: Middleware Integration
```php
// app/Http/Middleware/RequireTwoFactor.php
class RequireTwoFactor
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->requiresTwoFactor() && !$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup')
                ->with('warning', 'Tài khoản của bạn yêu cầu bật xác thực 2 yếu tố để tiếp tục sử dụng.');
        }

        return $next($request);
    }
}

// Modify LoginController to handle 2FA challenge
public function login(Request $request)
{
    // ... existing validation ...

    if ($this->attemptLogin($request)) {
        $user = $this->guard()->user();
        
        if ($user->hasTwoFactorEnabled()) {
            $this->guard()->logout();
            
            $request->session()->put([
                'two_factor.user_id' => $user->id,
                'two_factor.remember_me' => $request->filled('remember')
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->sendLoginResponse($request);
    }

    // ... rest of method ...
}
```

### Step 8: Frontend Views
```blade
{{-- resources/views/auth/two-factor/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Xác thực 2 Yếu tố (2FA)</div>
                
                <div class="card-body">
                    @if($user->hasTwoFactorEnabled())
                        <div class="alert alert-success">
                            <i class="fas fa-shield-alt"></i>
                            Xác thực 2 yếu tố đã được kích hoạt cho tài khoản của bạn.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Mã khôi phục</h5>
                                <p class="text-muted">Lưu trữ các mã này ở nơi an toàn. Bạn có thể sử dụng chúng để truy cập tài khoản nếu mất thiết bị xác thực.</p>
                                <div id="recovery-codes">
                                    @foreach($recoveryCodes as $code)
                                        <code class="d-block mb-1">{{ $code }}</code>
                                    @endforeach
                                </div>
                                <button class="btn btn-outline-primary btn-sm mt-2" onclick="regenerateRecoveryCodes()">
                                    Tạo mã mới
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h5>Tắt 2FA</h5>
                                <p class="text-muted">Tắt xác thực 2 yếu tố cho tài khoản của bạn.</p>
                                <button class="btn btn-danger" onclick="showDisableModal()">
                                    Tắt 2FA
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Xác thực 2 yếu tố chưa được kích hoạt. Hãy kích hoạt để bảo vệ tài khoản của bạn.
                        </div>

                        <button class="btn btn-primary" onclick="enableTwoFactor()">
                            Kích hoạt 2FA
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Setup Modal --}}
<div class="modal fade" id="setupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thiết lập xác thực 2 yếu tố</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="setup-step-1">
                    <h6>Bước 1: Quét mã QR</h6>
                    <p>Sử dụng ứng dụng Google Authenticator để quét mã QR sau:</p>
                    <div class="text-center">
                        <div id="qr-code"></div>
                        <p class="mt-2"><small>Hoặc nhập mã thủ công: <code id="manual-secret"></code></small></p>
                    </div>
                </div>
                
                <div id="setup-step-2" style="display: none;">
                    <h6>Bước 2: Xác nhận mã</h6>
                    <p>Nhập mã 6 chữ số từ ứng dụng authenticator:</p>
                    <form id="confirm-form">
                        <div class="mb-3">
                            <input type="text" class="form-control text-center" id="confirm-code" 
                                   placeholder="000000" maxlength="6" style="font-size: 1.5em; letter-spacing: 0.2em;">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Xác nhận</button>
                        </div>
                    </form>
                </div>

                <div id="setup-step-3" style="display: none;">
                    <h6>Mã khôi phục</h6>
                    <p>Lưu trữ các mã này ở nơi an toàn:</p>
                    <div id="backup-codes"></div>
                    <div class="alert alert-warning mt-3">
                        <strong>Quan trọng:</strong> Đây là lần duy nhất bạn có thể thấy các mã này. Hãy lưu trữ chúng ở nơi an toàn.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="continue-btn" onclick="continueSetup()">Tiếp tục</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
let setupData = null;

function enableTwoFactor() {
    fetch('/two-factor/enable', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setupData = data.data;
            showSetupModal();
        } else {
            alert(data.message);
        }
    });
}

function showSetupModal() {
    // Show QR code
    document.getElementById('qr-code').innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(setupData.qr_code)}" alt="QR Code">`;
    document.getElementById('manual-secret').textContent = setupData.secret;
    
    document.getElementById('setupModal').style.display = 'block';
    new bootstrap.Modal(document.getElementById('setupModal')).show();
}

function continueSetup() {
    if (currentStep === 1) {
        document.getElementById('setup-step-1').style.display = 'none';
        document.getElementById('setup-step-2').style.display = 'block';
        document.getElementById('continue-btn').style.display = 'none';
        currentStep = 2;
    }
}

document.getElementById('confirm-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const code = document.getElementById('confirm-code').value;
    
    fetch('/two-factor/confirm', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ code: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('setup-step-2').style.display = 'none';
            document.getElementById('setup-step-3').style.display = 'block';
            
            // Show backup codes
            const codesHtml = setupData.backup_codes.map(code => `<code class="d-block mb-1">${code}</code>`).join('');
            document.getElementById('backup-codes').innerHTML = codesHtml;
            
            // Change close button to finish
            document.querySelector('#setupModal .btn-secondary').textContent = 'Hoàn thành';
            document.querySelector('#setupModal .btn-secondary').onclick = function() {
                location.reload();
            };
        } else {
            alert(data.message);
        }
    });
});

function regenerateRecoveryCodes() {
    if (confirm('Bạn có chắc muốn tạo mã khôi phục mới? Các mã cũ sẽ không thể sử dụng được nữa.')) {
        fetch('/two-factor/recovery-codes', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const codesHtml = data.codes.map(code => `<code class="d-block mb-1">${code}</code>`).join('');
                document.getElementById('recovery-codes').innerHTML = codesHtml;
                alert('Mã khôi phục mới đã được tạo.');
            }
        });
    }
}
</script>
@endsection
```

## Testing Requirements

### Unit Tests
```php
// tests/Unit/TwoFactorServiceTest.php
class TwoFactorServiceTest extends TestCase
{
    public function test_can_enable_totp_for_user()
    {
        $user = User::factory()->create();
        $service = app(TwoFactorService::class);
        
        $result = $service->enableTotp($user);
        
        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('qr_code', $result);
        $this->assertArrayHasKey('backup_codes', $result);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_can_verify_totp_code()
    {
        $user = User::factory()->create();
        $service = app(TwoFactorService::class);
        
        $result = $service->enableTotp($user);
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp($result['secret']);
        
        $this->assertTrue($service->confirmTotp($user, $validCode));
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_can_use_recovery_code()
    {
        $user = User::factory()->create([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['test-code-1', 'test-code-2']
        ]);
        
        $this->assertTrue($user->useRecoveryCode('test-code-1'));
        $this->assertFalse($user->useRecoveryCode('test-code-1')); // Should fail second time
        $this->assertCount(1, $user->fresh()->two_factor_recovery_codes);
    }
}
```

### Feature Tests
```php
// tests/Feature/TwoFactorAuthTest.php
class TwoFactorAuthTest extends TestCase
{
    public function test_user_can_enable_two_factor_authentication()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/two-factor/enable');
            
        $response->assertOk();
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_user_must_confirm_with_valid_code()
    {
        $user = User::factory()->create();
        $service = app(TwoFactorService::class);
        $result = $service->enableTotp($user);
        
        $response = $this->actingAs($user)
            ->post('/two-factor/confirm', [
                'code' => '000000' // Invalid code
            ]);
            
        $response->assertStatus(400);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_login_redirects_to_two_factor_challenge()
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now()
        ]);
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $response->assertRedirect('/two-factor/challenge');
        $this->assertGuest();
    }
}
```

## Security Considerations

### 1. Rate Limiting
- SMS: 3 attempts per 5 minutes
- TOTP verification: 5 attempts per minute
- Recovery codes: 3 attempts per hour

### 2. Secret Storage
- Encrypt 2FA secrets in database
- Use secure random generation
- Proper secret rotation on disable/re-enable

### 3. Backup Methods
- Multiple recovery codes
- SMS backup (with phone verification)
- Admin emergency access with audit trail

### 4. Session Security
- Separate 2FA challenge session
- Proper session cleanup
- Time-limited challenge tokens

## Success Criteria
- [ ] Users can enable/disable 2FA with TOTP
- [ ] QR code generation works with authenticator apps
- [ ] Recovery codes can be generated and used
- [ ] SMS backup authentication functional
- [ ] Login flow includes 2FA challenge
- [ ] Rate limiting prevents brute force
- [ ] All security tests pass
- [ ] Audit trail for all 2FA events 
