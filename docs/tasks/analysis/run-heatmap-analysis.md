# Chạy Phân Tích Heatmap

## Mục đích
Hướng dẫn tạo và phân tích heatmap để theo dõi hiệu suất các cầu lô theo thời gian.

## Prerequisites
- Dữ liệu xổ số đã được import
- Formula hits đã được tính toán
- Redis/Cache đã được cấu hình
- Quyền chạy queue jobs

## Quy trình Phân Tích

### 1. Tạo Heatmap Data

```bash
# Tạo heatmap cho 5 ngày gần nhất
php artisan heatmap:generate --from=2024-01-01 --to=2024-01-05

# Tạo heatmap với tùy chọn retry (chạy lại cho ngày đã có)
php artisan heatmap:generate --from=2024-01-01 --to=2024-01-05 --retry

# Xóa dữ liệu cũ và tạo mới
php artisan heatmap:generate --from=2024-01-01 --to=2024-01-05 --delete
```

### 2. Phân Tích Heatmap

```bash
# Phân tích heatmap cho ngày hiện tại
php artisan heatmap:analyze

# Phân tích cho ngày cụ thể với 30 ngày dữ liệu
php artisan heatmap:analyze --date=2024-01-15 --days=30

# Phân tích với khoảng quan sát ngắn hơn
php artisan heatmap:analyze --date=2024-01-15 --days=15
```

## Steps Chi Tiết

### Bước 1: Chuẩn bị dữ liệu
```bash
# Kiểm tra dữ liệu formula hits
php artisan tinker
>>> App\Models\FormulaHit::count()
>>> App\Models\FormulaHit::orderBy('ngay', 'desc')->first()

# Kiểm tra queue đang chạy
php artisan queue:work --once
```

### Bước 2: Tạo heatmap records
```bash
# Monitor job progress
php artisan queue:work --timeout=300 &

# Chạy generate command
php artisan heatmap:generate --from=$(date -d '30 days ago' +%Y-%m-%d) --to=$(date +%Y-%m-%d)
```

### Bước 3: Phân tích insights
```bash
# Chạy phân tích để tạo insights
php artisan heatmap:analyze --days=30

# Kiểm tra kết quả
php artisan tinker
>>> App\Models\FormulaHeatmapInsight::count()
>>> App\Models\FormulaHeatmapInsight::latest()->take(5)->get()
```

## Verification

### 1. Kiểm tra Heatmap Records
```sql
-- Kiểm tra dữ liệu heatmap đã tạo
SELECT date, JSON_LENGTH(data) as formula_count 
FROM heatmap_daily_records 
ORDER BY date DESC LIMIT 10;

-- Kiểm tra chi tiết một ngày
SELECT data FROM heatmap_daily_records 
WHERE date = '2024-01-15';
```

### 2. Kiểm tra Insights
```sql
-- Xem insights đã tạo
SELECT insight_type, COUNT(*) as count
FROM formula_heatmap_insights 
GROUP BY insight_type;

-- Chi tiết insights theo loại
SELECT * FROM formula_heatmap_insights 
WHERE insight_type = 'long_run' 
ORDER BY insight_score DESC LIMIT 10;
```

### 3. Kiểm tra qua Web Interface
```bash
# Truy cập heatmap page
curl -I http://localhost/heatmap

# Kiểm tra analytic page
curl -I http://localhost/heatmap/analytic
```

## Các Loại Insight

### 1. Long Run
- Cầu lô chạy dài không trúng
- Điểm insight cao = khả năng sắp trúng

### 2. Rebound After Long Run
- Cầu lô vừa kết thúc chuỗi dài
- Theo dõi hiệu suất sau khi trúng

### 3. Long Run Stop
- Cầu lô dừng chuỗi dài
- Phân tích pattern dừng

## Troubleshooting

### Lỗi thường gặp

#### 1. "No formula hits found"
```bash
# Kiểm tra dữ liệu formula hits
php artisan lottery:check-formulas --days=30

# Generate formula statistics nếu cần
php artisan formula:generate-statistics 1 2024-01-01 30
```

#### 2. "Queue job failed"
```bash
# Kiểm tra failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

#### 3. "Memory limit exceeded"
```bash
# Chạy với memory limit cao hơn
php -d memory_limit=512M artisan heatmap:analyze

# Giảm số ngày phân tích
php artisan heatmap:analyze --days=15
```

#### 4. "Empty heatmap data"
```bash
# Kiểm tra heatmap records
php artisan tinker
>>> App\Models\HeatmapDailyRecord::count()

# Regenerate nếu cần
php artisan heatmap:generate --from=2024-01-01 --to=2024-01-15 --delete
```

## Performance Optimization

### 1. Batch Processing
```bash
# Chia nhỏ khoảng thời gian
for i in {1..30}; do
  date=$(date -d "$i days ago" +%Y-%m-%d)
  php artisan heatmap:generate --from=$date --to=$date
done
```

### 2. Background Jobs
```bash
# Chạy queue worker với nhiều process
php artisan queue:work --queue=heatmap --processes=4
```

### 3. Cache Management
```bash
# Clear cache trước khi phân tích
php artisan cache:clear

# Warm up cache sau phân tích
php artisan route:cache
```

## Monitoring

### 1. Log Analysis
```bash
# Monitor heatmap generation
tail -f storage/logs/laravel.log | grep heatmap

# Check for errors
grep "ERROR" storage/logs/laravel.log | grep heatmap
```

### 2. Performance Metrics
```bash
# Check database size
php artisan tinker
>>> DB::select("SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('heatmap_daily_records', 'formula_heatmap_insights')")
```

## Best Practices

1. **Chạy heatmap analysis định kỳ hàng ngày**
2. **Monitor queue jobs thường xuyên**
3. **Backup trước khi chạy analysis lớn**
4. **Sử dụng cache để tăng tốc độ truy vấn**
5. **Giới hạn số ngày phân tích để tránh timeout**

## Automation

### Cron Job Setup
```bash
# Thêm vào crontab
0 1 * * * cd /path/to/project && php artisan heatmap:generate --from=$(date -d '1 days ago' +%Y-%m-%d) --to=$(date +%Y-%m-%d)
0 2 * * * cd /path/to/project && php artisan heatmap:analyze --days=30
```

## References
- [HeatmapInsightService Documentation](../../services/HeatmapInsightService.md)
- [FormulaHitService Documentation](../../services/FormulaHitService.md)
- [Queue Configuration](../development/queue-setup.md) 
