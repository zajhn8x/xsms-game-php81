# Hướng Dẫn Setup Hệ Thống Đặt Cược

## Tổng Quan

Hệ thống đặt cược đã được triển khai với các tính năng chính:

### ✅ Đã Hoàn Thành (Phase 1)

1. **Hệ thống ví điện tử** - WalletService
   - Ví thật, ví ảo, ví bonus
   - Nạp tiền, rút tiền, chuyển đổi
   - Lịch sử giao dịch

2. **Đặt cược thử nghiệm lịch sử** - HistoricalTestingService
   - Test chiến lược trên dữ liệu quá khứ
   - Multiple strategies: manual, heatmap, streak
   - Background job processing

3. **Dashboard analytics** - DashboardService
   - User dashboard với biểu đồ
   - Admin dashboard
   - Real-time charts

4. **Models & Migrations**
   - Wallet, WalletTransaction
   - HistoricalCampaign, HistoricalBet
   - Cập nhật Campaign, User models

5. **Controllers & Routes**
   - WalletController
   - HistoricalTestingController
   - DashboardController

## Cài Đặt

### 1. Chạy Migrations

```bash
# Đảm bảo database đang chạy
# Cập nhật .env với thông tin database

php artisan migrate
```

### 2. Cấu Hình Queue (Cho Historical Testing)

```bash
# .env
QUEUE_CONNECTION=database

# Chạy queue worker
php artisan queue:work
```

### 3. Tạo Admin User (Tùy Chọn)

```bash
php artisan tinker

# Trong tinker:
$admin = App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
    'subscription_type' => 'admin'
]);
```

## Cấu Trúc Code

### Services

- **WalletService**: Quản lý ví điện tử
- **HistoricalTestingService**: Xử lý test lịch sử
- **TimeTravelBettingEngine**: Engine cho simulation
- **DashboardService**: Analytics và dashboard

### Models

- **Wallet**: Ví điện tử
- **WalletTransaction**: Giao dịch ví
- **HistoricalCampaign**: Chiến dịch test lịch sử
- **HistoricalBet**: Cược test lịch sử

### Controllers

- **WalletController**: API ví điện tử
- **HistoricalTestingController**: Quản lý test lịch sử
- **DashboardController**: Dashboard và analytics

## Routes Chính

### User Routes

```
/dashboard - Dashboard user
/wallet - Quản lý ví
/wallet/history - Lịch sử giao dịch
/historical-testing - Test lịch sử
/campaigns - Quản lý chiến dịch
```

### API Routes

```
/api/dashboard/user - Dashboard data
/api/dashboard/chart/{type} - Chart data
/historical-testing/api - Historical testing APIs
```

### Admin Routes

```
/admin/dashboard - Admin dashboard
/wallet/admin/withdrawal/{id}/process - Xử lý rút tiền
```

## Tính Năng Chính

### 1. Hệ Thống Ví

- **Ví Thật**: Tiền thật có thể rút
- **Ví Ảo**: Tiền ảo để test (1M VND mặc định)
- **Ví Bonus**: Tiền từ khuyến mãi
- **Tự động tạo ví**: Khi user đăng ký

### 2. Đặt Cược Thử Nghiệm

- **4 Strategies**: Manual, Heatmap, Streak, Hybrid
- **Background processing**: Chạy test qua queue
- **Real-time progress**: Theo dõi tiến độ
- **Detailed results**: Phân tích chi tiết

### 3. Dashboard Analytics

- **User Dashboard**: 
  - Tổng quan ví
  - Hiệu suất chiến dịch
  - Biểu đồ lợi nhuận
  - Hoạt động gần đây

- **Admin Dashboard**:
  - System overview
  - User analytics
  - Financial metrics
  - System charts

## Testing

### Test Ví Điện Tử

```bash
# User được tạo sẽ tự động có:
# - Real balance: 0 VND
# - Virtual balance: 1,000,000 VND
# - Bonus balance: 0 VND
```

### Test Historical Testing

```bash
# Tạo test campaign
# Chọn strategy và config
# Chạy test (sẽ chạy background)
# Xem kết quả real-time
```

## Roadmap Tiếp Theo

### Phase 2: Advanced Features (6-8 tuần)

- [ ] Real-time campaigns
- [ ] Social features (follow, share)
- [ ] Advanced risk management
- [ ] Mobile optimization
- [ ] Payment gateway integration

### Phase 3: Scale & Optimize (4-6 tuần)

- [ ] Performance optimization
- [ ] Caching strategies
- [ ] API rate limiting
- [ ] Advanced security
- [ ] Monitoring & logging

## Troubleshooting

### Database Issues

```bash
# Nếu có lỗi migration
php artisan migrate:fresh

# Nếu cần seed data
php artisan db:seed
```

### Queue Issues

```bash
# Nếu job không chạy
php artisan queue:restart
php artisan queue:work --tries=3
```

### Permission Issues

```bash
# Nếu có lỗi permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Support

Để được hỗ trợ:

1. Kiểm tra log: `storage/logs/laravel.log`
2. Kiểm tra queue jobs: `php artisan queue:failed`
3. Debug với: `php artisan tinker`

## Security Notes

- Passwords được hash
- CSRF protection enabled
- Database transactions cho data integrity
- Input validation
- XSS protection

---

**Lưu ý**: Đây là Phase 1 implementation. Các tính năng advanced sẽ được triển khai trong các phase tiếp theo. 
