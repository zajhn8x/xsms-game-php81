# FormulaHitService - Tài liệu sử dụng

## Giới thiệu
`FormulaHitService` là service phục vụ các nghiệp vụ phân tích cầu lô, heatmap, timeline, thống kê streak, sinh danh sách lô, ... cho hệ thống xổ số.

## Các chức năng chính

### 1. Lấy danh sách công thức có streak liên tiếp
```php
getStreakFormulas($fromDate, $streak = 2, $limit = 3)
```
- Trả về danh sách các công thức có số ngày trúng liên tiếp từ `$fromDate` trở về trước.
- `$streak`: số ngày liên tiếp.
- `$limit`: số lượng kết quả tối đa.

### 2. Lấy dữ liệu heatmap cho nhiều cầu lô trong nhiều ngày
```php
getHeatMap($endDate = null)
```
- Trả về mảng heatmap theo ngày, mỗi ngày gồm danh sách cầu lô và streak tương ứng.
- Mặc định lấy 7 ngày gần nhất.

### 3. Lấy dữ liệu timeline cho một công thức
```php
getTimelineData(LotteryFormula $cauLo, Carbon $startDate, int $daysBack = 30)
```
- Trả về dữ liệu timeline (meta, dateRange, hits, results, resultIndexs) cho 1 cầu lô từ `$startDate` lùi về `$daysBack` ngày.

### 4. Lấy thông tin hit và streak theo danh sách cầu lô và ngày
```php
getHitDataWithStreak(array $cauLoIds, array $dates): array
```
- Trả về mảng thông tin hit, status, streak cho từng cầu lô theo từng ngày.

### 5. Lấy dữ liệu hit cho nhiều cầu lô trong nhiều ngày
```php
getHitData(array $cauLoIds, array $dates): array
```
- Trả về mảng dữ liệu hit (id, value, hit, status, streak) cho từng cầu lô, từng ngày.

### 6. Tính streak của 1 cầu lô tới ngày bất kỳ
```php
private calculateStreak($cauLoId, $date): int
```
- Trả về số ngày trúng liên tiếp của 1 cầu lô tính tới ngày `$date`.

### 7. Sinh danh sách lô từ kết quả công thức
```php
getLotteryListByFormulas(array $formulasData): array
```
- Trả về danh sách lô (bình thường và forward only) từ dữ liệu kết quả công thức.

### 8. Tìm các công thức trúng liên tiếp trong nhiều ngày
```php
findConsecutiveHits($date, $days = 3)
```
- Trả về danh sách các công thức trúng liên tiếp trong `$days` ngày tính tới `$date`.

## Mô hình hóa nghiệp vụ
- Service này phụ thuộc vào các model: `LotteryFormula`, `FormulaHit`, `LotteryResult`, ...
- Nên sử dụng qua DI container của Laravel để tự động inject các service/phụ thuộc.

## Ví dụ sử dụng
```php
$service = app(\App\Services\FormulaHitService::class);
$heatmap = $service->getHeatMap('2024-06-01');
$timeline = $service->getTimelineData($formula, now(), 30);
```

## Lưu ý
- Nên sử dụng service này thay vì truy vấn trực tiếp model để đảm bảo logic nghiệp vụ và dễ bảo trì.
- Có thể inject vào controller hoặc các service khác qua DI container của Laravel.

---
*Xem chi tiết code trong file `app/Services/FormulaHitService.php` để biết thêm các hàm phụ trợ và logic xử lý.* 