# Quản Lý Formula và Cầu Lô

## Mục đích
Hướng dẫn tạo, kiểm tra, và quản lý các formula cầu lô trong hệ thống.

## Prerequisites
- Dữ liệu xổ số đã được import đầy đủ
- Database schema đã được migrate
- Queue system đã được cấu hình
- Hiểu biết về cấu trúc vị trí XSMB

## Quản Lý Formula Meta

### 1. Tạo Formula Pair Tự Động

```bash
# Tạo 1000 formula pair ngẫu nhiên
php artisan lottery:generate-pair-formulas 1000

# Tạo số lượng nhỏ để test
php artisan lottery:generate-pair-formulas 10

# Kiểm tra kết quả
php artisan tinker
>>> App\Models\LotteryFormulaMeta::where('combination_type', 'pair')->count()
```

### 2. Tạo Formula Manual

```php
// Trong tinker hoặc seeder
$formula = new LotteryFormulaMeta([
    'formula_name' => 'Cầu Lô ĐB-G1',
    'formula_note' => 'Ghép từ vị trí đặc biệt và giải nhất',
    'formula_structure' => json_encode([
        'positions' => ['GDB-1-1', 'G1-1-1'],
        'description' => 'Ghép cầu từ chữ số đầu ĐB và G1'
    ]),
    'combination_type' => 'pair'
]);
$formula->save();
```

## Quản Lý Formula Processing

### 1. Chuẩn Bị Formula cho Xử Lý

```bash
# Tạo các formula instance từ meta (lấy 10 formula mới)
php artisan lottery:check-formulas --get-new=10

# Kiểm tra các formula đã chuẩn bị
php artisan tinker
>>> App\Models\LotteryFormula::where('is_processed', false)->count()
>>> App\Models\LotteryFormula::where('processing_status', 'pending')->count()
```

### 2. Chạy Kiểm Tra Formula

```bash
# Kiểm tra formula cho 30 ngày gần nhất với 2 formula mỗi batch
php artisan lottery:check-formulas --days=30 --max-formula-batch=2

# Kiểm tra từ ngày cụ thể
php artisan lottery:check-formulas --start-date=2024-01-01 --days=30

# Xử lý tiếp các formula đang dừng (partial status)
php artisan lottery:check-formulas --partial --days=30
```

### 3. Tạo Statistics cho Formula

```bash
# Generate statistics cho formula ID 1, từ ngày 2024-01-01, trong 90 ngày
php artisan formula:generate-statistics 1 2024-01-01 90

# Batch processing theo quý
php artisan formula:generate-statistics 1 2024-01-01 365
```

## Verification và Monitoring

### 1. Kiểm Tra Trạng Thái Formula

```sql
-- Xem tổng quan các formula
SELECT 
    processing_status,
    COUNT(*) as count,
    AVG(processed_days) as avg_days
FROM lottery_formulas 
GROUP BY processing_status;

-- Formula đang được xử lý
SELECT id, formula_meta_id, processed_days, last_processed_date
FROM lottery_formulas 
WHERE processing_status = 'processing'
ORDER BY last_processed_date DESC;

-- Formula hoàn thành
SELECT f.id, fm.formula_name, f.processed_days
FROM lottery_formulas f
JOIN lottery_formula_meta fm ON f.formula_meta_id = fm.id
WHERE f.is_processed = true
ORDER BY f.processed_days DESC;
```

### 2. Kiểm Tra Formula Hits

```sql
-- Thống kê hits theo formula
SELECT 
    cau_lo_id,
    COUNT(*) as total_hits,
    MAX(streak) as max_streak,
    AVG(streak) as avg_streak
FROM formula_hit 
GROUP BY cau_lo_id
ORDER BY total_hits DESC
LIMIT 20;

-- Formula có hiệu suất tốt nhất
SELECT 
    fh.cau_lo_id,
    fm.formula_name,
    COUNT(*) as hits,
    AVG(fh.streak) as avg_streak
FROM formula_hit fh
JOIN lottery_formulas lf ON fh.cau_lo_id = lf.id
JOIN lottery_formula_meta fm ON lf.formula_meta_id = fm.id
GROUP BY fh.cau_lo_id, fm.formula_name
HAVING hits > 10
ORDER BY avg_streak DESC;
```

### 3. Kiểm Tra Statistics

```sql
-- Statistics overview
SELECT 
    formula_id,
    COUNT(*) as stats_count,
    AVG(hit_rate) as avg_hit_rate,
    AVG(avg_streak) as avg_streak
FROM formula_statistics 
GROUP BY formula_id
ORDER BY avg_hit_rate DESC;
```

## Troubleshooting

### Lỗi thường gặp

#### 1. "No formulas to process"
```bash
# Kiểm tra có formula meta chưa
php artisan tinker
>>> App\Models\LotteryFormulaMeta::count()

# Tạo formula mới nếu cần
php artisan lottery:generate-pair-formulas 50

# Chuẩn bị formula cho processing
php artisan lottery:check-formulas --get-new=10
```

#### 2. "Formula processing stuck"
```bash
# Xem các formula đang stuck
php artisan tinker
>>> App\Models\LotteryFormula::where('processing_status', 'processing')->where('updated_at', '<', now()->subHours(2))->get()

# Reset formula bị stuck
>>> App\Models\LotteryFormula::where('processing_status', 'processing')->where('updated_at', '<', now()->subHours(2))->update(['processing_status' => 'partial'])
```

#### 3. "Queue jobs failed"
```bash
# Kiểm tra failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry [job-id]

# Restart queue worker
php artisan queue:restart
```

#### 4. "Memory limit exceeded"
```bash
# Chạy với memory limit cao hơn
php -d memory_limit=1024M artisan lottery:check-formulas --days=30

# Giảm batch size
php artisan lottery:check-formulas --days=30 --max-formula-batch=1
```

### Reset Formula

```bash
# Reset một formula cụ thể
php artisan formula:reset 123

# Reset tất cả formula có issue
php artisan tinker
>>> App\Models\LotteryFormula::where('processing_status', 'error')->update(['is_processed' => false, 'processing_status' => 'pending', 'processed_days' => 0])
```

## Best Practices

### 1. Formula Creation
- Tạo formula từ từ để tránh quá tải hệ thống
- Kiểm tra duplicate trước khi tạo
- Đặt tên formula có ý nghĩa

### 2. Processing Management
- Chạy processing trong giờ thấp điểm
- Monitor queue worker thường xuyên
- Backup trước khi chạy batch lớn

### 3. Performance Optimization
```bash
# Tối ưu database
php artisan db:index:optimize

# Clear cache định kỳ
php artisan cache:clear

# Monitor resource usage
htop
```

## Monitoring và Automation

### 1. Daily Processing Cron Job

```bash
# Thêm vào crontab
# Chuẩn bị formula mới mỗi ngày
0 2 * * * cd /path/to/project && php artisan lottery:check-formulas --get-new=5

# Xử lý formula pending
0 3 * * * cd /path/to/project && php artisan lottery:check-formulas --days=7 --max-formula-batch=3

# Xử lý formula partial
0 4 * * * cd /path/to/project && php artisan lottery:check-formulas --partial --days=7
```

### 2. Health Check Script

```bash
#!/bin/bash
# formula-health-check.sh

echo "=== Formula Health Check ==="

# Check pending formulas
PENDING=$(php artisan tinker --execute="echo App\Models\LotteryFormula::where('processing_status', 'pending')->count();")
echo "Pending formulas: $PENDING"

# Check stuck processing
STUCK=$(php artisan tinker --execute="echo App\Models\LotteryFormula::where('processing_status', 'processing')->where('updated_at', '<', now()->subHours(2))->count();")
echo "Stuck formulas: $STUCK"

# Check failed jobs
FAILED=$(php artisan queue:failed | wc -l)
echo "Failed jobs: $FAILED"

if [ $STUCK -gt 0 ]; then
    echo "WARNING: Found stuck formulas, please investigate"
fi
```

### 3. Performance Monitoring

```bash
# Monitor processing progress
watch -n 30 'php artisan tinker --execute="
echo \"Pending: \" . App\Models\LotteryFormula::where(\"processing_status\", \"pending\")->count();
echo \"Processing: \" . App\Models\LotteryFormula::where(\"processing_status\", \"processing\")->count(); 
echo \"Completed: \" . App\Models\LotteryFormula::where(\"is_processed\", true)->count();
"'
```

## Advanced Usage

### 1. Custom Formula Types

```php
// Tạo formula triplet (3 vị trí)
$formula = new LotteryFormulaMeta([
    'formula_name' => 'Cầu Lô Triplet ĐB-G1-G2',
    'formula_structure' => json_encode([
        'positions' => ['GDB-1-1', 'G1-1-1', 'G2-1-1'],
        'combination_method' => 'triplet'
    ]),
    'combination_type' => 'triplet'
]);
```

### 2. Batch Operations

```bash
# Batch reset multiple formulas
php artisan tinker
>>> $formulas = App\Models\LotteryFormula::where('processing_status', 'error')->pluck('id');
>>> foreach($formulas as $id) { \Artisan::call('formula:reset', ['id' => $id]); }
```

## References
- [FormulaHitService Documentation](../../services/FormulaHitService.md)
- [Database Schema](../development/database-schema.md)
- [Queue Configuration](../development/queue-setup.md)
- [XSMB Position Configuration](../development/xsmb-positions.md) 
