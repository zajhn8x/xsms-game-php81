# Hướng dẫn thiết lập Google OAuth và Facebook Login

## 1. Cấu hình Google OAuth

### Bước 1: Tạo Google Cloud Project
1. Truy cập [Google Cloud Console](https://console.cloud.google.com/)
2. Tạo project mới hoặc chọn project hiện có
3. Vào `APIs & Services > Credentials`

### Bước 2: Cấu hình OAuth 2.0
1. Nhấn `Create Credentials > OAuth 2.0 Client IDs`
2. Chọn `Web application`
3. Thêm Authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (development)
   - `https://yourdomain.com/auth/google/callback` (production)

### Bước 3: Cấu hình Environment Variables
Thêm vào file `.env`:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

## 2. Cấu hình Facebook Login

### Bước 1: Tạo Facebook App
1. Truy cập [Facebook Developers](https://developers.facebook.com/)
2. Tạo app mới với type "Consumer"
3. Thêm Facebook Login product

### Bước 2: Cấu hình OAuth Settings
1. Vào `Facebook Login > Settings`
2. Thêm Valid OAuth Redirect URIs:
   - `http://localhost:8000/auth/facebook/callback` (development)
   - `https://yourdomain.com/auth/facebook/callback` (production)

### Bước 3: Cấu hình Environment Variables
Thêm vào file `.env`:

```env
# Facebook OAuth
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_REDIRECT_URI=${APP_URL}/auth/facebook/callback
```

## 3. Testing Social Login

### Routes để test:
- Login với Google: `/auth/google/redirect`
- Login với Facebook: `/auth/facebook/redirect`

### Callback URLs:
- Google callback: `/auth/google/callback`
- Facebook callback: `/auth/facebook/callback`

## 4. Tính năng đã implement

### Social Authentication Controller
- `redirectToProvider()`: Redirect đến provider
- `handleProviderCallback()`: Xử lý callback từ provider
- `linkAccount()`: Liên kết tài khoản social với user hiện tại
- `unlinkAccount()`: Hủy liên kết tài khoản social

### Database Changes
- Thêm cột `google_id`, `facebook_id`, `provider` vào bảng `users`
- Password trở thành nullable cho social users
- Email được verify tự động khi đăng nhập social

### User Model Updates
- Thêm `google_id`, `facebook_id`, `provider` vào `$fillable`

### Features
1. **Đăng nhập/đăng ký mới**: User có thể tạo tài khoản mới qua social login
2. **Liên kết tài khoản**: User có email trùng sẽ được liên kết automatic
3. **Wallet tự động**: Tạo wallet cho user mới với trial subscription 7 ngày
4. **Security**: Kiểm tra social ID đã được dùng chưa
5. **Error handling**: Xử lý lỗi và hiển thị thông báo phù hợp

### UI Components
- Nút Google/Facebook login trong form đăng nhập và đăng ký
- Thiết kế responsive với Tailwind CSS
- Icons chính thức của Google và Facebook
- Hover effects và animations
- Divider "Hoặc đăng nhập với"

### Security Considerations
1. State verification trong OAuth flow
2. Email verification tự động cho social users
3. Proper error handling và logging
4. IP tracking cho last login
5. Kiểm tra tài khoản đã liên kết

## 5. Cấu hình Production

### Environment Variables bổ sung:
```env
# Production URLs
APP_URL=https://yourdomain.com
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
FACEBOOK_REDIRECT_URI=https://yourdomain.com/auth/facebook/callback

# Logging
LOG_CHANNEL=daily
LOG_SLACK_WEBHOOK_URL=your_slack_webhook_for_errors
```

### SSL Requirements
- HTTPS bắt buộc cho production OAuth
- Secure cookies configuration
- Proper CORS settings

## 6. Troubleshooting

### Common Issues:
1. **Redirect URI mismatch**: Kiểm tra chính xác URL trong developer console
2. **Missing scopes**: Google cần scope `email` và `profile`
3. **App not verified**: Facebook app cần review cho production
4. **HTTPS required**: Production phải dùng HTTPS

### Debug Commands:
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Check routes
php artisan route:list | grep auth

# Test social login flow
php artisan tinker
>>> app('Laravel\Socialite\Contracts\Factory')->driver('google')->redirect()
```

## 7. Migration Commands đã chạy

```bash
php81 ~/composer.phar require laravel/socialite
php artisan make:migration add_social_fields_to_users_table --table=users
php artisan migrate
```

## 8. Code Updates Summary

### Files Modified:
- `config/services.php`: Thêm Google/Facebook config
- `app/Models/User.php`: Thêm social fields vào fillable
- `routes/web.php`: Thêm social auth routes
- `resources/views/auth/login.blade.php`: Thêm social login buttons
- `resources/views/auth/register.blade.php`: Thêm social register buttons
- `resources/views/compass-theme-examples.blade.php`: Thêm examples và CSS

### Files Created:
- `app/Http/Controllers/Auth/SocialAuthController.php`: Social auth logic
- `database/migrations/add_social_fields_to_users_table.php`: Database changes

Sau khi cấu hình xong các environment variables, hệ thống social login sẽ hoạt động hoàn chỉnh với đầy đủ tính năng bảo mật và user experience tốt. 
