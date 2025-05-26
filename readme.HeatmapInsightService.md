# HeatmapInsightService (CQRS) - Tài liệu sử dụng

## Giới thiệu
`HeatmapInsightService` trước đây là service chịu trách nhiệm phân tích dữ liệu heatmap, phát hiện và lưu trữ insight về các cầu lô theo từng ngày. Để chuẩn hóa kiến trúc, service này đã được tách thành 2 service riêng biệt theo mô hình CQRS:
- `App\Services\Commands\HeatmapInsightCommandService`: Xử lý phân tích, ghi và lưu insight vào DB.
- `App\Services\Queries\HeatmapInsightQueryService`: Truy vấn, lọc, lấy insight để show analytic.

## Các chức năng chính

### 1. Xử lý toàn bộ heatmap và tạo insight (Command Service)
```php
$commandService = new HeatmapInsightCommandService($lotteryIndexResultsService);
$commandService->process($heatmapData);
```
- Duyệt qua toàn bộ dữ liệu heatmap, phân tích trạng thái cầu lô từng ngày, xác định các loại insight (long_run, long_run_stop, rebound_after_long_run) và lưu vào DB.

### 2. Tạo insight mới hoặc cập nhật insight cũ (Command Service)
```php
HeatmapInsightCommandService::createInsight(int $formulaId, string $date, string $type, string $extra, float $score = 0)
```
- Tạo mới hoặc cập nhật insight cho một cầu lô vào ngày cụ thể với loại insight, thông tin phụ và điểm số.

### 3. Truy vấn insight (Query Service)
```php
$queryService = new HeatmapInsightQueryService();
$insights = $queryService->getTopInsights($date, $type, $limit);
```
- Lấy danh sách insight tốt nhất theo ngày, loại, limit.

## Mô hình hóa nghiệp vụ
- Insight heatmap được phân loại thành 3 loại chính:

### 1. TYPE_LONG_RUN
- **Điều kiện**: Cầu lô có streak >= 6 ngày trúng liên tiếp
- **Thông tin lưu trữ**:
  - `streak_length`: Độ dài streak hiện tại
  - `value`: Giá trị cầu lô
  - `suggests`: Các gợi ý
- **Điểm số**: = độ dài streak (streak càng dài điểm càng cao)

### 2. TYPE_LONG_RUN_STOP
- **Điều kiện**: 
  - Đã từng là long_run (streak >= 6)
  - Không hit trong 1-3 ngày liên tiếp
- **Thông tin lưu trữ**:
  - `streak_length`: Độ dài streak trước khi dừng
  - `day_stop`: Số ngày đã dừng (1-3)
  - `value`: Giá trị cầu lô
  - `suggests`: Các gợi ý
- **Điểm số**: = streak_length - day_stop
- **Ví dụ**:
  ```
  Ngày 1: streak = 7 (long_run)
  Ngày 2: không hit, day_stop = 1 (long_run_stop)
  Ngày 3: không hit, day_stop = 2 (long_run_stop)
  Ngày 4: không hit, day_stop = 3 (long_run_stop)
  Ngày 5: không hit, không còn là long_run_stop
  ```

### 3. TYPE_REBOUND
- **Thuật toán timeline lùi về quá khứ:**
  - Thêm key ngày anchor (ngày phân tích) vào config
  - Ví dụ: $anchor_date = '2025-05-21';
  - Chỉ xét các ngày từ $anchor_date lùi về quá khứ

#### Bước 1: Lấy tất cả các id có streak >= streak_min trong khoảng ngày
- Duyệt từ ngày mới nhất về trước (date-2, date-3, ...)
- Với mỗi ngày, lấy các id có streak >= streak_min
- Lưu vào mảng: `$ids_by_date[$date] = [id1, id2, ...];`
- Nếu id đã xuất hiện ở ngày sau thì không lưu nữa (chỉ lấy lần đầu tiên xuất hiện gần nhất)

#### Bước 2: Tạo timeline lùi về quá khứ cho từng id
- Với mỗi id, tạo timeline từ ngày nó xuất hiện trong $ids_by_date về trước
- Timeline chứa thông tin: date, streak, value, suggests
- Thứ tự ngày giảm dần (timeline[0] là ngày xuất hiện, timeline[1] là ngày trước đó, ...)

#### Bước 3: Phân tích timeline để phát hiện TYPE_REBOUND
- Duyệt từ cuối timeline lên (từ ngày cũ nhất đến ngày mới nhất)
- Khi gặp streak >= streak_min đầu tiên:
  - Lưu lại streak_length = streak
  - Bắt đầu đếm stop_days (số ngày streak = 0)
  - Nếu stop_days > day_stops_max thì dừng phân tích id này

- Khi gặp streak = 1:
  - Nếu là ngày đầu tiên (j=0) và chưa có stop_days:
    - running = "step_1"
    - step_1, step_2, step_3 = null
  - Nếu không phải ngày đầu và j <= running_max+1:
    - Đánh dấu flag_stop_days = true
    - running = "step_1"
    - step_1, step_2, step_3 = null
  - Nếu đã có running_max_value > 0 và flag_stop_days:
    - Tăng running_max_value
    - Nếu running_max_value = 2:
      - running = "step_2"
      - step_1 = streak > 0 ? "hit" : "miss"
    - Nếu running_max_value = 3:
      - running = "step_3"
      - step_2 = streak > 0 ? "hit" : "miss"
      - step_3 = null

- Tạo insight TYPE_REBOUND khi:
  - Đang ở ngày anchor_date
  - stop_days > 0
  - Đang ở phần tử đầu tiên của timeline (j=0)

- **Thông tin lưu trữ trong insight:**
  - `streak_length`: Độ dài streak ban đầu (>= streak_min)
  - `stop_days`: Số ngày streak = 0 liên tiếp
  - `running`: Trạng thái hiện tại (step_1, step_2, step_3)
  - `step_1`: Kết quả hit/miss của step 1
  - `step_2`: Kết quả hit/miss của step 2
  - `step_3`: Kết quả hit/miss của step 3
  - `value`: Giá trị cầu lô
  - `suggests`: Các gợi ý

- **Điểm số**: = streak_length

- **Ví dụ**:
  ```
  Timeline: [streak=1, streak=0, streak=0, streak=7]
  - streak_length = 7
  - stop_days = 2
  - running = "step_1"
  - step_1, step_2, step_3 = null
  ```

## Ví dụ sử dụng
### Phân tích và lưu insight:
```php
$commandService = new \App\Services\Commands\HeatmapInsightCommandService($lotteryIndexResultsService);
$commandService->process($heatmapData);
```

### Truy vấn insight analytic:
```php
$queryService = new \App\Services\Queries\HeatmapInsightQueryService();
$insights = $queryService->getTopInsights('2025-05-20', 'long_run', 50);
```

## Sử dụng command artisan
### Phân tích heatmap và lưu insight qua command:
```bash
php artisan heatmap:analyze --date=2025-05-20 --days=30
```
- `--date`: Ngày cần phân tích (nếu không truyền sẽ lấy ngày hiện tại)
- `--days`: Số ngày cần phân tích (mặc định 30)
- Command này sẽ tự động gọi service để phân tích và lưu insight vào DB.

## Lưu ý
- Service này cần truyền vào dữ liệu heatmap (mảng) và một instance của LotteryIndexResultsService.
- Nên chạy process sau khi đã có đầy đủ dữ liệu kết quả xổ số và index.
- Có thể mở rộng logic phân tích insight theo nhu cầu nghiệp vụ.

---
*Code chi tiết đã được tách thành 2 file:*
- `app/Services/Commands/HeatmapInsightCommandService.php`
- `app/Services/Queries/HeatmapInsightQueryService.php` 
