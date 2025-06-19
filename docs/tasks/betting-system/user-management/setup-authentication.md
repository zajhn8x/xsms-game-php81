# Thiết lập Hệ thống Đăng ký/Đăng nhập

## Tổng quan
Task này thiết lập hệ thống authentication cơ bản cho ứng dụng betting, bao gồm đăng ký, đăng nhập, xác thực email và quản lý session.

## Mục tiêu
- Implement registration & login system
- Email verification workflow
- Password reset functionality
- Session management
- Remember me functionality
- Rate limiting cho login attempts

## Technical Requirements

### 1. User Registration
- Email uniqueness validation
- Password strength requirements
- Email verification mandatory
- Auto wallet creation
- Welcome email sending

### 2. User Login
- Email/username + password
- Remember me option
- Account lockout after failed attempts
- Login activity logging
- Redirect to intended page

### 3. Password Security
- Minimum 8 characters
- Mix of letters, numbers, symbols
- Password confirmation
- Password history (prevent reuse)
- Secure password reset

## Implementation Steps

### Step 1: Update User Model
```php
// app/Models/User.php
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasWallet;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'date_of_birth',
        'avatar', 'is_active', 'email_verified_at', 'last_login_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean'
    ];

    public function getAvatarUrlAttribute()
    {
        return $this->avatar 
            ? Storage::url($this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
}
```

### Step 2: Authentication Controllers
```php
// app/Http/Controllers/Auth/RegisterController.php
class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $this->validateRegistration($request);
        
        $user = $this->createUser($request->all());
        
        // Send verification email
        $user->sendEmailVerificationNotification();
        
        // Create wallet
        $this->createUserWallet($user);
        
        // Log registration
        activity()
            ->causedBy($user)
            ->log('User registered');

        return redirect()->route('verification.notice')
            ->with('success', 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản.');
    }

    protected function validateRegistration(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'terms' => 'required|accepted'
        ], [
            'password.regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt',
            'terms.accepted' => 'Bạn phải đồng ý với điều khoản sử dụng'
        ]);
    }

    protected function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'is_active' => true
        ]);
    }

    protected function createUserWallet($user)
    {
        $user->wallet()->create([
            'real_balance' => 0,
            'bonus_balance' => 50000, // Welcome bonus 50k
            'currency' => 'VND'
        ]);
    }
}
```

### Step 3: Login Controller
```php
// app/Http/Controllers/Auth/LoginController.php
class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Check if user is locked out
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required|captcha'
        ], [
            'g-recaptcha-response.required' => 'Vui lòng xác thực captcha',
            'g-recaptcha-response.captcha' => 'Captcha không hợp lệ'
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        $credentials = $this->credentials($request);
        $credentials['is_active'] = true;

        $remember = $request->filled('remember');

        return $this->guard()->attempt($credentials, $remember);
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        // Update last login time
        auth()->user()->update(['last_login_at' => now()]);

        // Log login activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('User logged in');

        return $this->authenticated($request, $this->guard()->user())
            ?: redirect()->intended($this->redirectPath());
    }

    protected function credentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    public function logout(Request $request)
    {
        // Log logout activity
        if (auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->log('User logged out');
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

### Step 4: Email Verification
```php
// app/Http/Controllers/Auth/VerificationController.php
class VerificationController extends Controller
{
    use VerifiesEmails;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect($this->redirectPath())
            : view('auth.verify');
    }

    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid verification link');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath())->with('verified', true);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            
            // Give welcome bonus after verification
            $this->giveWelcomeBonus($request->user());
        }

        return redirect($this->redirectPath())->with('verified', true);
    }

    protected function giveWelcomeBonus($user)
    {
        if ($user->wallet && $user->wallet->bonus_balance == 50000) {
            // Add additional verification bonus
            $user->wallet->increment('bonus_balance', 100000);
            
            // Log transaction
            $user->wallet->transactions()->create([
                'type' => 'bonus',
                'balance_type' => 'bonus',
                'amount' => 100000,
                'description' => 'Email verification bonus',
                'status' => 'completed',
                'processed_at' => now()
            ]);
        }
    }
}
```

### Step 5: Password Reset
```php
// app/Http/Controllers/Auth/ForgotPasswordController.php
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        // Log password reset request
        if ($user = User::where('email', $request->email)->first()) {
            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Password reset requested');
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    protected function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'g-recaptcha-response' => 'required|captcha'
        ]);
    }
}

// app/Http/Controllers/Auth/ResetPasswordController.php
class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/dashboard';

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ];
    }

    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Log password reset
        activity()
            ->causedBy($user)
            ->log('Password reset completed');

        event(new PasswordReset($user));
    }
}
```

### Step 6: Middleware & Guards
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],

// app/Http/Middleware/CheckEmailVerified.php
class CheckEmailVerified
{
    public function handle($request, Closure $next)
    {
        if (!$request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}

// app/Http/Middleware/CheckAccountStatus.php
class CheckAccountStatus
{
    public function handle($request, Closure $next)
    {
        if (!$request->user()->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
        }

        return $next($request);
    }
}
```

### Step 7: Routes Configuration
```php
// routes/web.php
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
});

Route::middleware(['auth', 'verified', 'account.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Other protected routes...
});
```

## Testing Requirements

### Unit Tests
```php
// tests/Feature/Auth/RegistrationTest.php
class RegistrationTest extends TestCase
{
    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('wallets', ['user_id' => User::where('email', 'test@example.com')->first()->id]);
    }

    public function test_password_must_meet_requirements()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms' => true
        ]);

        $response->assertSessionHasErrors('password');
    }
}
```

### Integration Tests
```php
// tests/Feature/Auth/LoginTest.php  
class LoginTest extends TestCase
{
    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_account_lockout_after_multiple_failed_attempts()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}
```

## Security Considerations

### 1. Rate Limiting
- 5 login attempts per 15 minutes
- 6 email verification attempts per minute
- 3 password reset requests per hour

### 2. CSRF Protection
- All forms protected with CSRF tokens
- API routes protected with Sanctum

### 3. Input Validation
- Server-side validation for all inputs
- XSS protection through Laravel's built-in escaping
- SQL injection prevention through Eloquent ORM

### 4. Session Security
- Secure session configuration
- Session regeneration on login
- Proper logout handling

## Deployment Checklist

### Environment Configuration
- [ ] Set strong APP_KEY
- [ ] Configure mail settings
- [ ] Set up reCAPTCHA keys
- [ ] Configure session/cache drivers
- [ ] Set secure session cookies

### Database
- [ ] Run migrations
- [ ] Seed default roles/permissions
- [ ] Set up database indexes

### Security
- [ ] Enable HTTPS
- [ ] Configure security headers
- [ ] Set up rate limiting
- [ ] Configure firewall rules

## Monitoring & Logging

### Key Metrics
- Registration conversion rate
- Login success/failure rates
- Email verification rates
- Password reset frequency
- Account lockout incidents

### Log Events
- User registration
- Login attempts (success/failure)
- Email verification
- Password resets
- Account lockouts
- Suspicious activities

## Success Criteria
- [ ] Users can register with email verification
- [ ] Users can login with remember me option
- [ ] Password reset works via email
- [ ] Account lockout prevents brute force
- [ ] All security tests pass
- [ ] 95% email delivery rate
- [ ] < 2 second response time for auth endpoints 
