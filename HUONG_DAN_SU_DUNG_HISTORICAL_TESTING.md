# HƯỚNG DẪN SỬ DỤNG HỆ THỐNG HISTORICAL TESTING

## 🎯 Tổng quan
Hệ thống Historical Testing cho phép bạn chạy thử các chiến lược đặt cược với dữ liệu xổ số từ quá khứ để đánh giá hiệu quả trước khi áp dụng với tiền thật.

## 🚀 Cài đặt và Chuẩn bị

### 1. Import dữ liệu xổ số lịch sử
```bash
# Import từ file CSV mẫu
php artisan lottery:import xsmb_mau.csv

# Hoặc import từ API (7 ngày gần nhất)
php artisan lottery:import-api 7
```

### 2. Kiểm tra dữ liệu đã import
```bash
php artisan tinker
>>> App\Models\LotteryResult::count()
>>> App\Models\LotteryResult::orderBy('draw_date', 'desc')->take(5)->get(['draw_date'])
```

## 🎮 Cách sử dụng

### 1. Chạy test đơn giản
```bash
# Test cơ bản với cài đặt mặc định
php artisan campaign:test-historical

# Test với các số cụ thể
php artisan campaign:test-historical --numbers=12,34,56,78 --bet-amount=20000

# Test với khoảng thời gian khác
php artisan campaign:test-historical --start-date=2005-11-01 --end-date=2005-11-30
```

### 2. So sánh nhiều chiến thuật
```bash
# So sánh 3 chiến thuật: Conservative, Aggressive, Balanced
php artisan campaign:test-historical --compare

# So sánh với số dư ban đầu khác
php artisan campaign:test-historical --compare --balance=2000000
```

### 3. Tùy chọn nâng cao
```bash
# Test với chiến thuật tích cực
php artisan campaign:test-historical \
    --numbers=07,17,27,37,47,57 \
    --bet-amount=25000 \
    --max-bets=5 \
    --balance=5000000

# Test với chiến thuật bảo thủ
php artisan campaign:test-historical \
    --numbers=01,11,22,33 \
    --bet-amount=5000 \
    --max-bets=2 \
    --balance=1000000
```

## 📊 Các tham số cấu hình

| Tham số | Mặc định | Mô tả |
|---------|----------|-------|
| `--start-date` | 2005-10-01 | Ngày bắt đầu test (Y-m-d) |
| `--end-date` | 2005-10-31 | Ngày kết thúc test (Y-m-d) |
| `--balance` | 1000000 | Số dư ban đầu (VNĐ) |
| `--strategy` | manual | Chiến thuật (manual, auto_heatmap, auto_streak) |
| `--bet-amount` | 10000 | Số tiền đặt mỗi lần (VNĐ) |
| `--numbers` | random | Các số target (phân cách bằng dấu phẩy) |
| `--max-bets` | 3 | Số lần đặt tối đa mỗi ngày |
| `--user-email` | test@example.com | Email user test |
| `--compare` | false | So sánh nhiều chiến thuật |

## 📈 Hiểu kết quả

### Các chỉ số quan trọng:
- **ROI (Return on Investment)**: Tỷ lệ lợi nhuận (%)
- **Tỷ lệ thắng**: Phần trăm số lần đặt thắng
- **Lãi/Lỗ**: Số tiền lãi hoặc lỗ thực tế
- **Số lần đặt**: Tổng số lần đặt cược

### Ví dụ kết quả:
```
🎯 KẾT QUẢ CUỐI CÙNG
===================
| Trạng thái      | completed      |
| Số dư ban đầu   | 1,000,000 VNĐ  |
| Số dư cuối cùng | 18,805,000 VNĐ |
| Lãi/Lỗ          | 17,805,000 VNĐ |
| ROI             | 1,780.50%      |
| Tổng lần đặt    | 93             |
| Lần thắng       | 16             |
| Tỷ lệ thắng     | 17.2%          |
```

## 🎭 Các chiến thuật test sẵn

### 1. Conservative (Bảo thủ)
- Số tiền đặt: 5,000 VNĐ/lần
- Các số target: 01, 11, 22, 33
- Tối đa 2 lần đặt/ngày
- **Đặc điểm**: Rủi ro thấp, lợi nhuận ổn định

### 2. Aggressive (Tích cực)
- Số tiền đặt: 20,000 VNĐ/lần
- Các số target: 07, 17, 27, 37, 47, 57
- Tối đa 5 lần đặt/ngày
- **Đặc điểm**: Rủi ro cao, lợi nhuận cao

### 3. Balanced (Cân bằng)
- Số tiền đặt: 10,000 VNĐ/lần
- Các số target: 12, 34, 56
- Tối đa 3 lần đặt/ngày
- **Đặc điểm**: Cân bằng giữa rủi ro và lợi nhuận

## 🔬 Ví dụ thực tế

### Test 1: Chiến thuật số đẹp
```bash
php artisan campaign:test-historical \
    --numbers=08,18,28,38,48,58,68,78,88,98 \
    --bet-amount=8000 \
    --max-bets=4
```

### Test 2: Chiến thuật theo ngày sinh
```bash
php artisan campaign:test-historical \
    --numbers=15,07,1988 \
    --bet-amount=15000 \
    --max-bets=3
```

### Test 3: Chiến thuật ngẫu nhiên
```bash
php artisan campaign:test-historical \
    --bet-amount=12000 \
    --max-bets=5
```

## 📊 So sánh kết quả

Khi chạy `--compare`, hệ thống sẽ test đồng thời 3 chiến thuật và hiển thị bảng so sánh:

```
🏆 KẾT QUẢ SO SÁNH
==================
| Hạng | Chiến thuật  | ROI       | Lãi/Lỗ (VNĐ) | Tỷ lệ thắng | Số lần đặt |
| #1   | Aggressive   | 4,170.00% | 41,700,000   | 18.1%       | 155        |
| #2   | Balanced     | 1,187.00% | 11,870,000   | 17.2%       | 93         |
| #3   | Conservative | 489.00%   | 4,890,000    | 21%         | 62         |
```

## 🌐 Xem chi tiết qua Web Interface

Sau khi chạy test, bạn có thể xem chi tiết tại:
- `http://localhost/historical-testing/{campaign_id}`

Giao diện web hiển thị:
- Biểu đồ lãi/lỗ theo ngày
- Danh sách chi tiết các lần đặt
- Phân tích số may mắn
- So sánh với các campaign khác

## 🛠️ Tùy chỉnh nâng cao

### Tạo chiến thuật riêng:
```php
// Trong TimeTravelBettingEngine.php
private function customStrategy($campaign, $currentDate)
{
    $config = $campaign->strategy_config;
    
    // Logic chiến thuật của bạn
    $targetNumbers = $this->analyzeHistoricalPatterns($currentDate);
    
    $betsToPlace = [];
    foreach ($targetNumbers as $number) {
        $betsToPlace[] = [
            'number' => $number,
            'amount' => $config['bet_amount'],
            'notes' => 'Custom strategy bet'
        ];
    }
    
    return $betsToPlace;
}
```

### Import dữ liệu riêng:
```bash
# Từ file CSV
php artisan lottery:import your_data.csv

# Từ API
php artisan lottery:import-api 30
```

## ⚠️ Lưu ý quan trọng

1. **Kết quả quá khứ không đảm bảo hiệu quả tương lai**
2. **Luôn test với số tiền nhỏ trước khi áp dụng thực tế**
3. **Chỉ đầu tư số tiền có thể chấp nhận mất**
4. **Kết hợp nhiều chiến thuật để giảm rủi ro**

## 🔧 Troubleshooting

### Lỗi thiếu dữ liệu:
```bash
# Kiểm tra dữ liệu lottery
php artisan tinker
>>> App\Models\LotteryResult::count()

# Import thêm dữ liệu nếu cần
php artisan lottery:import-api 30
```

### Lỗi memory:
```bash
# Tăng memory limit
php -d memory_limit=2G artisan campaign:test-historical
```

### Lỗi timeout:
```bash
# Giảm khoảng thời gian test
php artisan campaign:test-historical --start-date=2005-10-01 --end-date=2005-10-07
```

## 📞 Hỗ trợ

- 📧 Email: support@xsmb-game.com
- 📱 Telegram: @xsmb_support
- 🌐 Website: https://xsmb-game.com/docs

---

🎉 **Chúc bạn test thành công và tìm được chiến thuật hiệu quả!** 
