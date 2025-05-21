# LotteryIndexResultsService - Tài liệu sử dụng

## Giới thiệu
`LotteryIndexResultsService` là service phục vụ truy vấn, kiểm tra, và phân tích dữ liệu chỉ số kết quả xổ số (theo từng vị trí), hỗ trợ các nghiệp vụ heatmap, phân tích cầu lô, kiểm tra dữ liệu đầy đủ, ...

## Các chức năng chính

### 1. Lấy giá trị tại vị trí cụ thể cho một ngày xổ số
```php
getPositionValue($date, $position)
```
- Trả về giá trị tại 1 hoặc nhiều vị trí cho ngày xổ số `$date`.
- `$position` có thể là string hoặc mảng (ví dụ: 'GDB-1-1', ['G2-2-1', 'G5-3-2']).

### 2. Lấy tất cả giá trị vị trí cho một ngày cụ thể
```php
getPositionsForDate($date)
```
- Trả về map [position => value] cho toàn bộ các vị trí trong ngày `$date`.

### 3. Lấy danh sách các ngày có dữ liệu xổ số theo vị trí
```php
getDrawDates(array $positions, $startDate = null, $endDate = null)
```
- Trả về danh sách các ngày có dữ liệu cho các vị trí chỉ định, có thể lọc theo khoảng ngày.

### 4. Kiểm tra ngày xổ số có dữ liệu đầy đủ không
```php
isDateComplete($date)
```
- Trả về true nếu ngày `$date` đã có đủ dữ liệu cho tất cả các vị trí cấu hình.

### 5. Lấy tất cả các vị trí từ cấu hình
```php
getAllConfigPositions()
```
- Trả về danh sách tất cả các vị trí xổ số từ file config.

### 6. Truy vấn lịch sử giá trị của một vị trí cụ thể
```php
getPositionHistory($position, $limit = 30, $endDate = null)
```
- Trả về lịch sử giá trị của 1 vị trí, giới hạn số lượng và có thể chỉ lấy đến ngày `$endDate`.

### 7. Lấy giá trị các vị trí theo nhiều formula_id cho 1 ngày
```php
getPositionsByFormulaIds(array $ids, string $date)
```
- Trả về mảng: mỗi formula_id sẽ có map [position => value] cho ngày `$date`.

## Mô hình hóa nghiệp vụ
- Service này là lớp trung gian truy vấn dữ liệu bảng `LotteryResultIndex` (kết quả xổ số theo từng vị trí).
- Hỗ trợ kiểm tra dữ liệu đầy đủ, truy vấn nhanh cho heatmap, analytic, ...
- Có thể mở rộng thêm các phương thức phân tích, thống kê theo vị trí.

## Ví dụ sử dụng
```php
$service = app(\App\Services\LotteryIndexResultsService::class);
$values = $service->getPositionValue('2024-06-01', ['G2-2-1', 'G5-3-2']);
$isFull = $service->isDateComplete('2024-06-01');
$history = $service->getPositionHistory('G2-2-1', 10);
$byFormula = $service->getPositionsByFormulaIds([22, 23], '2024-06-01');
```

## Lưu ý
- Nên sử dụng service này thay vì truy vấn trực tiếp model để đảm bảo logic nghiệp vụ và dễ bảo trì.
- Có thể inject vào controller hoặc các service khác qua DI container của Laravel.
- Các vị trí hợp lệ lấy từ config `xsmb.positions`.

---
*Xem chi tiết code trong file `app/Services/LotteryIndexResultsService.php` để biết thêm các hàm phụ trợ và logic xử lý.* 