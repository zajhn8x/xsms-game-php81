# HeatmapInsightService - Tài liệu sử dụng

## Giới thiệu
`HeatmapInsightService` là service chịu trách nhiệm phân tích dữ liệu heatmap, phát hiện và lưu trữ insight về các cầu lô theo từng ngày, phục vụ cho việc đánh giá, thống kê và tối ưu chiến lược chơi lô.

## Các chức năng chính

### 1. Xử lý toàn bộ heatmap và tạo insight
```php
process()
```
- Duyệt qua toàn bộ dữ liệu heatmap, phân tích trạng thái cầu lô từng ngày, xác định các loại insight (long_run, long_run_stop, rebound_after_long_run) và lưu vào DB.

### 2. Tạo insight mới hoặc cập nhật insight cũ
```php
createInsight(int $formulaId, string $date, string $type, array $extra, float $score = 0)
```
- Tạo mới hoặc cập nhật insight cho một cầu lô vào ngày cụ thể với loại insight, thông tin phụ và điểm số.

## Mô hình hóa nghiệp vụ
- Insight heatmap được phân loại thành:
  - **long_run**: Cầu lô có streak >= 6 ngày trúng liên tiếp.
  - **long_run_stop**: Sau khi cầu lô đạt long_run (streak >= 6), nếu những ngày tiếp theo không hit thì chuyển sang long_run_stop (tối đa 3 ngày). Nếu sau 3 ngày vẫn không hit thì không còn là long_run_stop nữa. Nếu hit lại thì chuyển sang rebound hoặc reset trạng thái. Dùng để nhận diện các cầu lô vừa kết thúc chuỗi thắng dài và đang trong giai đoạn "nghỉ".
  - **rebound_after_long_run**: Cầu lô vừa dừng streak và trúng lại sau khi dừng.
- Mỗi insight lưu các thông tin: streak_length, day_stop, value, hit, predicted_values_by_position, ...
- Dữ liệu được lưu vào bảng `formula_heatmap_insights`.

### Ví dụ về long_run_stop
- Ngày 1: streak = 7 (long_run)
- Ngày 2: không hit, day_stop = 1 (long_run_stop)
- Ngày 3: không hit, day_stop = 2 (long_run_stop)
- Ngày 4: không hit, day_stop = 3 (long_run_stop)
- Ngày 5: không hit, không còn là long_run_stop

**Ví dụ thực tế với dữ liệu heatmap:**
- Ngày 18/05/2025: Cầu lô 113 đạt streak = 7 ⇒ long_run
- Ngày 19/05/2025: Không hit, bắt đầu long_run_stop, day_stop = 1
- Ngày 20/05/2025: Không hit tiếp, long_run_stop, day_stop = 2
=> Đúng nghiệp vụ, ngày 20/05/2025, cầu lô id 113 phải là long_run_stop, day_stop = 2.

## Ví dụ sử dụng
```php
$service = new HeatmapInsightService($heatmapData, $lotteryIndexResultsService);
$service->process();
```

## Lưu ý
- Service này cần truyền vào dữ liệu heatmap (mảng) và một instance của LotteryIndexResultsService.
- Nên chạy process sau khi đã có đầy đủ dữ liệu kết quả xổ số và index.
- Có thể mở rộng logic phân tích insight theo nhu cầu nghiệp vụ.

---
*Xem chi tiết code trong file `app/Services/HeatmapInsightService.php` để biết thêm các hàm phụ trợ và logic xử lý.* 