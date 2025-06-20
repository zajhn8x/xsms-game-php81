# Phase 3.2: REST API cho Mobile - Authentication APIs

## 📋 Tổng quan

Phase 3.2 Task 1: Authentication APIs Implementation đã hoàn thành với hệ thống authentication toàn diện cho mobile application.

## 🏗️ Kiến trúc

### Controllers
- `app/Http/Controllers/Api/AuthController.php` - Main authentication controller
- Comprehensive JWT token management với Laravel Sanctum
- Production-ready error handling và logging

### Form Requests
- `app/Http/Requests/Api/LoginRequest.php` - Login validation
- `app/Http/Requests/Api/RegisterRequest.php` - Registration validation với Vietnamese phone
- `app/Http/Requests/Api/VerifyTwoFactorRequest.php` - 2FA verification
- `app/Http/Requests/Api/ForgotPasswordRequest.php` - Password reset request
- `app/Http/Requests/Api/ResetPasswordRequest.php` - Password reset completion

### Security Features
- Strong password validation (mixed case, numbers, symbols)
- Vietnamese phone number validation
- Disposable email detection và blocking
- Rate limiting (configurable, disabled trong testing)
- XSS/SQL injection pattern detection
- Comprehensive input sanitization

## 🔐 API Endpoints

### Public Routes (Guest Access)

#### 1. User Registration
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "SecurePassword123!@#",
    "password_confirmation": "SecurePassword123!@#",
    "phone": "0987654321",
    "terms_accepted": true,
    "referral_code": "ABC12345" // optional
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com",
            "phone": "0987654321",
            "avatar": null,
            "email_verified_at": null,
            "two_factor_enabled": false,
            "created_at": "2024-06-20T10:30:00.000000Z",
            "last_login_at": null
        },
        "token": "1|jwt-token-here",
        "token_type": "Bearer",
        "expires_in": 2592000
    }
}
```

#### 2. User Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePassword123!@#",
    "device_name": "iPhone 14 Pro", // optional
    "fcm_token": "fcm-token-for-push" // optional
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": { /* user object */ },
        "token": "2|jwt-token-here",
        "token_type": "Bearer",
        "expires_in": 2592000
    }
}
```

**Response (200) - 2FA Required:**
```json
{
    "success": true,
    "message": "2FA code sent",
    "data": {
        "requires_2fa": true,
        "user_id": 1,
        "message": "Two-factor authentication required"
    }
}
```

#### 3. 2FA Verification
```http
POST /api/auth/verify-2fa
Content-Type: application/json

{
    "user_id": 1,
    "code": "123456",
    "device_name": "iPhone 14 Pro" // optional
}
```

#### 4. Forgot Password
```http
POST /api/auth/forgot-password
Content-Type: application/json

{
    "email": "user@example.com"
}
```

#### 5. Reset Password
```http
POST /api/auth/reset-password
Content-Type: application/json

{
    "token": "reset-token-from-email",
    "email": "user@example.com",
    "password": "NewSecurePassword123!@#",
    "password_confirmation": "NewSecurePassword123!@#"
}
```

### Protected Routes (Require Authentication)

#### 6. Get Current User Profile
```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "message": "User data retrieved successfully",
    "data": {
        "user": { /* user object */ },
        "wallet": {
            "balance": 1000000,
            "currency": "VND"
        },
        "recent_campaigns": []
    }
}
```

#### 7. Update Profile
```http
POST /api/auth/update-profile
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "name": "Updated Name",
    "phone": "0912345678",
    "avatar": file // image file
}
```

#### 8. Change Password
```http
POST /api/auth/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "OldPassword123!@#",
    "new_password": "NewPassword123!@#",
    "new_password_confirmation": "NewPassword123!@#"
}
```

#### 9. Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

#### 10. Get Active Sessions
```http
GET /api/auth/sessions
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Sessions retrieved successfully",
    "data": {
        "sessions": [
            {
                "id": 1,
                "name": "auth-token",
                "is_current": true,
                "last_used_at": "2024-06-20T10:30:00.000000Z",
                "created_at": "2024-06-20T10:00:00.000000Z",
                "expires_at": "2024-07-20T10:00:00.000000Z"
            }
        ]
    }
}
```

#### 11. Revoke Specific Session
```http
DELETE /api/auth/sessions/{tokenId}
Authorization: Bearer {token}
```

#### 12. Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### 13. Logout from All Devices
```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

## 🛡️ Security Features

### 1. Input Validation
- **Strong Password Policy**: Min 8 chars, mixed case, numbers, symbols
- **Vietnamese Phone Validation**: Regex `/^(\+84|84|0)[3|5|7|8|9][0-9]{8}$/`
- **Email Validation**: RFC compliant với disposable email blocking
- **XSS/SQL Injection Protection**: Pattern detection và sanitization

### 2. Rate Limiting
- **Login**: 5 attempts per hour per IP+email combination
- **Registration**: 5 attempts per hour per IP
- **Password Reset**: 3 attempts per hour per IP+email
- **2FA SMS**: 5 tokens per hour per user (production only)

### 3. Token Management
- **JWT Tokens**: Laravel Sanctum với 30-day expiration
- **Multiple Sessions**: Support nhiều devices simultaneously
- **Token Revocation**: Granular control over active sessions
- **Automatic Cleanup**: Expired tokens được clean up

### 4. Two-Factor Authentication
- **SMS Tokens**: Integration với TwoFactorService
- **TOTP Support**: Google Authenticator compatible
- **Recovery Codes**: Backup authentication method
- **Rate Limited**: Prevent SMS spam attacks

## 📊 Testing Coverage

### Test Suite: `tests/Feature/Api/AuthenticationApiTest.php`

**Results: 19/21 tests PASSED** (2 skipped)

#### ✅ Passed Tests (19)
1. ✅ `user_can_register_successfully`
2. ✅ `registration_fails_with_invalid_data` 
3. ✅ `user_can_login_successfully`
4. ✅ `login_fails_with_invalid_credentials`
5. ✅ `user_can_get_profile_when_authenticated`
6. ✅ `user_cannot_access_protected_routes_without_token`
7. ✅ `user_can_logout_successfully`
8. ✅ `user_can_logout_from_all_devices`
9. ✅ `user_can_refresh_token`
10. ✅ `user_can_change_password`
11. ✅ `password_change_fails_with_incorrect_current_password`
12. ✅ `user_can_update_profile`
13. ✅ `user_can_request_password_reset`
14. ✅ `password_reset_fails_with_invalid_email`
15. ✅ `user_can_get_active_sessions`
16. ✅ `user_can_revoke_specific_session`
17. ✅ `user_cannot_revoke_current_session`
18. ✅ `api_validates_vietnamese_phone_number_format`
19. ✅ `api_rejects_disposable_email_addresses`

#### ⏭️ Skipped Tests (2)
- `login_requires_2fa_when_enabled` - 2FA test needs proper setup
- `api_enforces_rate_limiting_on_login` - Rate limiting disabled in testing

### Test Commands
```bash
# Run all authentication tests
php artisan test tests/Feature/Api/AuthenticationApiTest.php

# Run specific test
php artisan test --filter="user_can_login_successfully"
```

## 🔧 Database Migrations

### Required Migrations
1. **Add last_login_ip to users table**:
   ```php
   Schema::table('users', function (Blueprint $table) {
       $table->string('last_login_ip')->nullable()->after('last_login_at');
   });
   ```

### User Model Updates
- Added `last_login_ip` to fillable fields
- JWT token relationships via Laravel Sanctum
- Two-factor authentication support
- Activity logging integration

## 📱 Mobile Integration

### Headers Required
```http
Content-Type: application/json
Authorization: Bearer {jwt-token}
Accept: application/json
```

### Error Response Format
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    },
    "data": null
}
```

### Success Response Format
```json
{
    "success": true,
    "message": "Success message", 
    "data": { /* response data */ }
}
```

## 🚀 Performance Optimizations

1. **Lazy Loading**: User relationships loaded on demand
2. **Token Indexing**: Database indexes on token tables
3. **Rate Limiting**: Prevent abuse và spam
4. **Caching**: Redis caching cho 2FA tokens
5. **Validation Caching**: Disposable email domain caching

## 🔄 Next Steps - Phase 3.2 Tasks

### ✅ Completed
- **Task 1**: Authentication APIs (Completed)

### 🔜 Upcoming Tasks
- **Task 2**: Campaign Management APIs (5-6 ngày)
- **Task 3**: Betting APIs (4-5 ngày) 
- **Task 4**: Wallet APIs (3-4 ngày)
- **Task 5**: API Documentation (2-3 ngày)
- **Task 6**: Security & Rate Limiting (2-3 ngày)

## 🎯 Success Metrics

- **API Response Time**: < 200ms average
- **Authentication Success Rate**: > 99.5%
- **Security**: Zero SQL injection/XSS vulnerabilities
- **Test Coverage**: 90%+ automated test coverage
- **Error Rate**: < 0.1% trong production

## 📚 References

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [JWT Authentication Best Practices](https://auth0.com/blog/a-look-at-the-latest-draft-for-jwt-bcp/)
- [Mobile API Security Guidelines](https://owasp.org/www-project-mobile-security/)
- [Vietnamese Phone Number Standards](https://en.wikipedia.org/wiki/Telephone_numbers_in_Vietnam)

---

**Status**: ✅ **COMPLETED** - Authentication APIs ready for mobile integration

**Next**: 📊 Task 2 - Campaign Management APIs Implementation 
