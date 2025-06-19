# Password Reset System

## Tổng quan
Implement hệ thống reset mật khẩu an toàn qua email với các tính năng bảo mật nâng cao.

## Mục tiêu
- Reset mật khẩu qua email an toàn
- Rate limiting và anti-spam
- Password strength validation
- Audit trail cho security events
- Mobile-friendly reset process

## Implementation Steps

### Step 1: Password Reset Token Model
```php
// app/Models/PasswordResetToken.php
class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';
    protected $fillable = ['email', 'token', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
    public $timestamps = false;

    public function isExpired(): bool
    {
        return $this->created_at->addMinutes(config('auth.passwords.users.expire'))->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
```

### Step 2: Enhanced Controllers
```php
// app/Http/Controllers/Auth/ForgotPasswordController.php
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // Rate limiting per IP and email
        $this->checkRateLimit($request);

        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        if ($response == Password::RESET_LINK_SENT) {
            $this->logPasswordResetRequest($request);
            return $this->sendResetLinkResponse($request, $response);
        }

        return $this->sendResetLinkFailedResponse($request, $response);
    }

    protected function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'g-recaptcha-response' => 'required|captcha'
        ], [
            'email.exists' => 'Email không tồn tại trong hệ thống.',
            'g-recaptcha-response.required' => 'Vui lòng xác thực captcha.'
        ]);
    }

    protected function checkRateLimit(Request $request)
    {
        $key = 'password-reset:' . $request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts >= 5) {
            throw new TooManyRequestsHttpException(3600, 'Quá nhiều yêu cầu reset mật khẩu. Thử lại sau 1 giờ.');
        }

        Cache::put($key, $attempts + 1, 3600);
    }

    protected function logPasswordResetRequest(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        
        activity()
            ->causedBy($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ])
            ->log('Password reset requested');
    }
}
```

### Step 3: Secure Email Template
```blade
{{-- resources/views/emails/auth/reset-password.blade.php --}}
@component('mail::message')
# Reset Mật khẩu

Xin chào {{ $user->name }},

Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu reset mật khẩu cho tài khoản của bạn.

@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
Reset Mật khẩu
@endcomponent

**Thông tin bảo mật:**
- Link này sẽ hết hạn sau {{ config('auth.passwords.users.expire') }} phút
- Nếu bạn không yêu cầu reset mật khẩu, vui lòng bỏ qua email này
- IP yêu cầu: {{ $ipAddress }}
- Thời gian: {{ $requestTime }}

Nếu bạn gặp khó khăn khi click nút trên, copy và paste URL sau vào trình duyệt:
{{ $actionUrl }}

**Lưu ý bảo mật:**
- Không chia sẻ link này với ai khác
- Kiểm tra URL có bắt đầu bằng {{ config('app.url') }}
- Nếu nghi ngờ có hoạt động đáng ngờ, vui lòng liên hệ hỗ trợ

Trân trọng,<br>
{{ config('app.name') }}

---
Email này được gửi tự động, vui lòng không trả lời.
@endcomponent
```

### Step 4: Enhanced Reset Form
```blade
{{-- resources/views/auth/passwords/reset.blade.php --}}
@extends('layouts.auth')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Đặt lại mật khẩu</h2>
            <p class="text-muted">Nhập mật khẩu mới cho tài khoản của bạn</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" id="resetForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       id="email" value="{{ $email ?? old('email') }}" readonly>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu mới</label>
                <div class="password-input-group">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password" required autocomplete="new-password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <div class="password-requirements">
                    <small class="text-muted">
                        Mật khẩu phải có:
                        <ul class="mb-0">
                            <li id="length">Ít nhất 8 ký tự</li>
                            <li id="uppercase">1 chữ cái viết hoa</li>
                            <li id="lowercase">1 chữ cái viết thường</li>
                            <li id="number">1 chữ số</li>
                            <li id="special">1 ký tự đặc biệt</li>
                        </ul>
                    </small>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <div class="password-input-group">
                    <input type="password" class="form-control" 
                           id="password_confirmation" name="password_confirmation" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="passwordMatch"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn" disabled>
                <i class="fas fa-key"></i> Đặt lại mật khẩu
            </button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('login') }}" class="text-decoration-none">
                <i class="fas fa-arrow-left"></i> Quay lại đăng nhập
            </a>
        </div>
    </div>
</div>

<script>
// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
});

document.getElementById('password_confirmation').addEventListener('input', function() {
    checkPasswordMatch();
});

function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    // Update requirement indicators
    Object.keys(requirements).forEach(req => {
        const element = document.getElementById(req);
        if (requirements[req]) {
            element.classList.add('text-success');
            element.classList.remove('text-muted');
        } else {
            element.classList.add('text-muted');
            element.classList.remove('text-success');
        }
    });

    // Calculate strength
    const score = Object.values(requirements).filter(Boolean).length;
    const strengthBar = document.getElementById('passwordStrength');
    
    let strengthText = '';
    let strengthClass = '';
    
    switch(score) {
        case 0:
        case 1:
            strengthText = 'Rất yếu';
            strengthClass = 'strength-very-weak';
            break;
        case 2:
            strengthText = 'Yếu';
            strengthClass = 'strength-weak';
            break;
        case 3:
            strengthText = 'Trung bình';
            strengthClass = 'strength-medium';
            break;
        case 4:
            strengthText = 'Mạnh';
            strengthClass = 'strength-strong';
            break;
        case 5:
            strengthText = 'Rất mạnh';
            strengthClass = 'strength-very-strong';
            break;
    }
    
    strengthBar.innerHTML = `
        <div class="strength-meter">
            <div class="strength-bar ${strengthClass}" style="width: ${score * 20}%"></div>
        </div>
        <small class="strength-text">${strengthText}</small>
    `;

    updateSubmitButton();
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const matchDiv = document.getElementById('passwordMatch');

    if (confirmation && password !== confirmation) {
        matchDiv.innerHTML = '<small class="text-danger">Mật khẩu không khớp</small>';
    } else if (confirmation && password === confirmation) {
        matchDiv.innerHTML = '<small class="text-success">Mật khẩu khớp</small>';
    } else {
        matchDiv.innerHTML = '';
    }

    updateSubmitButton();
}

function updateSubmitButton() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const submitBtn = document.getElementById('submitBtn');

    const isValidPassword = password.length >= 8 && 
                           /[A-Z]/.test(password) && 
                           /[a-z]/.test(password) && 
                           /\d/.test(password) && 
                           /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    const isMatching = password === confirmation && confirmation.length > 0;

    submitBtn.disabled = !(isValidPassword && isMatching);
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}
</script>

<style>
.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
}

.password-requirements ul {
    font-size: 0.8em;
    padding-left: 1rem;
}

.password-requirements li.text-success::before {
    content: '✓ ';
    color: #28a745;
}

.strength-meter {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin: 5px 0;
}

.strength-bar {
    height: 100%;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.strength-very-weak { background-color: #dc3545; }
.strength-weak { background-color: #fd7e14; }
.strength-medium { background-color: #ffc107; }
.strength-strong { background-color: #20c997; }
.strength-very-strong { background-color: #28a745; }

.strength-text {
    display: block;
    margin-top: 2px;
    font-weight: 500;
}
</style>
@endsection
```

### Step 5: Custom Password Broker
```php
// app/Services/PasswordResetService.php
class PasswordResetService
{
    protected $tokenRepository;

    public function __construct(TokenRepositoryInterface $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    public function sendResetLink(array $credentials, \Closure $callback = null)
    {
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return Password::INVALID_USER;
        }

        if ($this->recentlyCreated($user->email)) {
            return 'passwords.throttled';
        }

        $token = $this->tokenRepository->create($user);

        if ($callback) {
            $callback($user, $token);
        } else {
            $this->emailResetLink($user, $token);
        }

        return Password::RESET_LINK_SENT;
    }

    protected function getUser(array $credentials)
    {
        return User::where('email', $credentials['email'])
                   ->where('is_active', true)
                   ->first();
    }

    protected function recentlyCreated($email)
    {
        $recent = PasswordResetToken::where('email', $email)
                                   ->where('created_at', '>', now()->subMinutes(1))
                                   ->exists();
        return $recent;
    }

    protected function emailResetLink($user, $token)
    {
        Mail::to($user->email)->send(
            new ResetPasswordMail($user, $token, request()->ip())
        );
    }

    public function reset(array $credentials, \Closure $callback)
    {
        $user = $this->validateReset($credentials);

        if (!$user instanceof User) {
            return $user;
        }

        $password = $credentials['password'];

        // Check password history
        if ($this->isPasswordReused($user, $password)) {
            return 'passwords.reused';
        }

        $callback($user, $password);

        $this->tokenRepository->delete($user);

        return Password::PASSWORD_RESET;
    }

    protected function validateReset(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return Password::INVALID_USER;
        }

        if (!$this->tokenRepository->exists($user, $credentials['token'])) {
            return Password::INVALID_TOKEN;
        }

        return $user;
    }

    protected function isPasswordReused($user, $password)
    {
        // Check against current password
        if (Hash::check($password, $user->password)) {
            return true;
        }

        // Check against last 5 passwords if you implement password history
        return false;
    }
}
```

### Step 6: Security Middleware
```php
// app/Http/Middleware/SecurePasswordReset.php
class SecurePasswordReset
{
    public function handle($request, Closure $next)
    {
        // Validate token format
        if ($request->has('token') && !$this->isValidToken($request->token)) {
            abort(404);
        }

        // Check if token is not expired
        if ($request->has(['token', 'email'])) {
            $tokenRecord = PasswordResetToken::where('email', $request->email)
                                            ->where('token', $request->token)
                                            ->first();

            if (!$tokenRecord || $tokenRecord->isExpired()) {
                return redirect()->route('password.request')
                    ->with('error', 'Link reset mật khẩu đã hết hạn. Vui lòng yêu cầu lại.');
            }
        }

        return $next($request);
    }

    protected function isValidToken($token)
    {
        return strlen($token) === 64 && ctype_alnum($token);
    }
}
```

## Testing Requirements

### Feature Tests
```php
// tests/Feature/PasswordResetTest.php
class PasswordResetTest extends TestCase
{
    public function test_password_reset_link_screen_can_be_rendered()
    {
        $response = $this->get('/password/reset');
        $response->assertStatus(200);
    }

    public function test_password_reset_link_can_be_requested()
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            $response->assertSessionHasNoErrors();
            return true;
        });
    }

    public function test_password_reset_requires_strong_password()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_is_rate_limited()
    {
        $user = User::factory()->create();

        // Send 6 requests rapidly
        for ($i = 0; $i < 6; $i++) {
            $this->post('/password/email', ['email' => $user->email]);
        }

        $response = $this->post('/password/email', ['email' => $user->email]);
        $response->assertStatus(429);
    }
}
```

## Success Criteria
- [ ] Password reset via email works securely
- [ ] Rate limiting prevents abuse
- [ ] Strong password validation enforced
- [ ] Mobile-friendly reset interface
- [ ] Expired tokens handled gracefully
- [ ] Security events logged properly
- [ ] Email deliverability > 95%
- [ ] Reset completion rate > 80% 
