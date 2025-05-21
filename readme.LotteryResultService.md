# LotteryResultService - Tài liệu sử dụng

## Giới thiệu
`LotteryResultService` là service quản lý và truy vấn dữ liệu kết quả xổ số, phục vụ cho các nghiệp vụ phân tích, thống kê, và lưu trữ kết quả.

## Các chức năng chính

### 1. Lấy kết quả xổ số mới nhất
```php
getLatestResults($limit)
```
- Trả về `$limit` kết quả xổ số mới nhất, sắp xếp theo ngày giảm dần.

### 2. Lấy kết quả xổ số theo khoảng ngày
```php
getResultsByDateRange($startDate, $endDate)
```
- Trả về danh sách kết quả xổ số từ `$startDate` đến `$endDate` (Y-m-d).

### 3. Tạo mới một kết quả xổ số
```php
createResult(array $data)
```
- Tạo mới một bản ghi kết quả xổ số với dữ liệu truyền vào.

### 4. Phân tích tần suất xuất hiện các số trong N ngày gần nhất
```php
analyzeFrequency($days)
```
- Trả về kết quả phân tích tần suất xuất hiện các số trong `$days` ngày gần nhất (cần tự cài đặt logic phân tích).

### 5. Kiểm tra đã có kết quả cho ngày cụ thể chưa
```php
hasResultForDate($date)
```
- Trả về true/false kiểm tra đã có kết quả cho ngày `$date` (Y-m-d).

## Mô hình hóa nghiệp vụ
- Mỗi bản ghi kết quả xổ số gồm các trường: `id`, `draw_date`, `result_data`, ...
- Service này là lớp trung gian giữa controller và model `LotteryResult`, giúp tách biệt logic truy vấn, tạo mới, và phân tích dữ liệu xổ số.
- Có thể mở rộng thêm các phương thức phân tích, thống kê, kiểm tra dữ liệu đầy đủ, ...

## Ví dụ sử dụng
```php
$service = app(\App\Services\LotteryResultService::class);
$latest = $service->getLatestResults(10);
$range = $service->getResultsByDateRange('2024-05-01', '2024-05-31');
$exists = $service->hasResultForDate('2024-06-01');
```

## Lưu ý
- Nên sử dụng service này thay vì truy vấn trực tiếp model để đảm bảo logic nghiệp vụ và dễ bảo trì.
- Có thể inject vào controller hoặc các service khác qua DI container của Laravel.

---
*Xem chi tiết code trong file `app/Services/LotteryResultService.php` để biết thêm các hàm phụ trợ và logic xử lý.* 