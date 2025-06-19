# Import Dữ Liệu Xổ Số

## Mục đích
Hướng dẫn import dữ liệu xổ số từ các nguồn khác nhau vào hệ thống.

## Prerequisites
- PHP artisan commands đã được setup
- Database đã được migrate
- Quyền truy cập file system
- API keys (nếu import từ API)

## Các phương thức Import

### 1. Import từ CSV File

```bash
# Import từ file CSV
php artisan lottery:import /path/to/file.csv

# Ví dụ với file mẫu
php artisan lottery:import xsmb_mau.csv
```

**Định dạng file CSV:**
- Cột 1: Ngày (format: d/m/Y)
- Cột 2-28: Các giải thưởng theo thứ tự
- Header row sẽ được bỏ qua

### 2. Import từ API

```bash
# Import 7 ngày gần nhất
php artisan lottery:import-api

# Import số ngày tùy chỉnh
php artisan lottery:import-api 30
```

**Nguồn API:** xoso188.net

### 3. Import từ JSON File

```bash
# Import từ file JSON
php artisan lottery:import /path/to/file.json
```

## Steps Thực Hiện

### Bước 1: Chuẩn bị dữ liệu
1. Kiểm tra định dạng file
2. Backup database hiện tại
3. Xác nhận khoảng thời gian cần import

### Bước 2: Thực hiện import
```bash
# Kiểm tra file trước khi import
head -5 your_file.csv

# Chạy import với verbose
php artisan lottery:import your_file.csv -v
```

### Bước 3: Kiểm tra kết quả
```bash
# Kiểm tra số lượng bản ghi đã import
php artisan tinker
>>> App\Models\LotteryResult::count()
>>> App\Models\LotteryResultIndex::count()
```

## Verification

### 1. Kiểm tra dữ liệu trong database
```sql
-- Kiểm tra kết quả xổ số
SELECT draw_date, prizes FROM lottery_results ORDER BY draw_date DESC LIMIT 5;

-- Kiểm tra index positions
SELECT draw_date, position, value FROM lottery_results_index 
WHERE draw_date = '2024-01-01' ORDER BY position;
```

### 2. Kiểm tra log
```bash
# Xem log import
tail -f storage/logs/laravel.log

# Tìm lỗi import
grep "ERROR" storage/logs/laravel.log | tail -10
```

## Troubleshooting

### Lỗi thường gặp

#### 1. "File not found"
```bash
# Kiểm tra đường dẫn file
ls -la /path/to/file.csv

# Sử dụng đường dẫn tuyệt đối
php artisan lottery:import $(pwd)/xsmb_mau.csv
```

#### 2. "Duplicate entry"
```bash
# Xóa dữ liệu trùng lặp
php artisan tinker
>>> App\Models\LotteryResult::where('draw_date', '2024-01-01')->delete();
```

#### 3. "Invalid date format"
- Kiểm tra định dạng ngày trong file CSV
- Đảm bảo format: dd/mm/yyyy

#### 4. "API connection failed"
```bash
# Kiểm tra kết nối internet
curl -I https://xoso188.net

# Thử lại với số ngày ít hơn
php artisan lottery:import-api 1
```

### Lệnh khôi phục
```bash
# Rollback nếu import sai
php artisan migrate:rollback

# Restore từ backup
mysql -u username -p database_name < backup.sql
```

## Best Practices

1. **Luôn backup trước khi import**
2. **Test với dữ liệu nhỏ trước**
3. **Kiểm tra log sau mỗi lần import**
4. **Verify dữ liệu sau import**
5. **Sử dụng transaction để đảm bảo tính toàn vẹn**

## Performance Tips

```bash
# Import với batch size lớn cho file lớn
php artisan lottery:import file.csv --batch-size=1000

# Tạo index sau khi import xong
php artisan db:index:create
```

## References
- [LotteryResultService Documentation](../../services/LotteryResultService.md)
- [Database Schema](../development/database-schema.md)
- [API Documentation](../external-apis/xoso188-api.md) 
