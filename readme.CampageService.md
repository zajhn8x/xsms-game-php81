# CampaignService - Tài liệu sử dụng

## Tính năng nổi bật
- **Quản lý nhiều chiến dịch chơi**: Người chơi có thể tạo và theo dõi nhiều chiến dịch chơi cùng lúc
- **Hệ thống vốn và lợi nhuận**: Theo dõi vốn đầu tư, lợi nhuận và hiệu quả của từng chiến lược
- **Giao diện trực quan**: Hiển thị thông tin chi tiết, thống kê và lịch sử cược
- **Quản lý trạng thái**: Tạm dừng, kết thúc chiến dịch linh hoạt

## Giới thiệu
`CampaignService` phục vụ cho việc quản lý các chiến dịch (Campaign) và các lượt đặt cược (CampaignBet) trong hệ thống phân tích và mô phỏng chiến lược chơi lô đề. Mỗi campaign đại diện cho một chiến lược hoặc một đợt chơi thử nghiệm, giúp đánh giá hiệu quả các phương pháp chọn cầu lô.

## Mô hình hóa dữ liệu

### Model: Campaign
- **id**: Khóa chính
- **name**: Tên chiến dịch
- **description**: Mô tả chiến dịch
- **start_date**: Ngày bắt đầu
- **end_date**: Ngày kết thúc (có thể null nếu chưa kết thúc)
- **status**: Trạng thái (running, paused, finished)
- **total_bet**: Tổng tiền cược
- **total_profit**: Tổng lợi nhuận
- **total_bet_count**: Số lần cược
- **win_rate**: Tỷ lệ thắng (%)
- **created_at, updated_at**: Thời gian tạo/cập nhật

### Model: CampaignBet
- **id**: Khóa chính
- **campaign_id**: Liên kết tới Campaign
- **bet_date**: Ngày đặt cược
- **bet_numbers**: Danh sách số lô đặt cược (JSON array)
- **bet_amount**: Số tiền đặt cược
- **result**: Số lần trúng (0 nếu thua)
- **profit**: Lợi nhuận (có thể âm)
- **created_at, updated_at**: Thời gian tạo/cập nhật

### Quan hệ
- Một Campaign có nhiều CampaignBet
- CampaignBet thuộc về một Campaign

## Chức năng chính của CampaignService

### Quản lý chiến dịch
- Tạo mới campaign (chiến dịch)
- Xem danh sách campaign
- Xem chi tiết campaign
- Tạm dừng/kết thúc campaign
- Xóa campaign

### Quản lý cược
- Thêm lượt đặt cược (bet) vào campaign
- Xem lịch sử cược
- Tính toán kết quả và lợi nhuận

### Thống kê & báo cáo
- Tính toán hiệu quả chiến dịch (tổng tiền cược, tổng lợi nhuận, tỷ lệ thắng...)
- Thống kê chi tiết từng ngày, từng số lô
- Lọc, tìm kiếm campaign theo trạng thái, thời gian, hiệu quả

## Giao diện người dùng

### 1. Danh sách chiến dịch (/campaigns)
- Hiển thị dạng bảng với các thông tin:
  - ID, tên chiến dịch
  - Ngày bắt đầu/kết thúc
  - Trạng thái (badge màu)
  - Tổng tiền cược, lợi nhuận
  - Tỷ lệ thắng
  - Các nút thao tác (xem chi tiết, tạm dừng, kết thúc)
- Nút tạo chiến dịch mới

### 2. Chi tiết chiến dịch (/campaigns/{id})
- Thông tin tổng quan:
  - Trạng thái, ngày bắt đầu/kết thúc
  - Mô tả chiến dịch
- Thống kê:
  - Tổng tiền cược
  - Lợi nhuận
  - Số lần cược
  - Tỷ lệ thắng
- Lịch sử cược:
  - Ngày cược
  - Số lô (badge)
  - Tiền cược
  - Kết quả (số lần trúng)
  - Lợi nhuận
- Modal thêm cược mới (nếu đang chạy)

### 3. Tạo chiến dịch mới (/campaigns/create)
- Form nhập thông tin:
  - Tên chiến dịch
  - Mô tả (tùy chọn)
  - Ngày bắt đầu
- Validate dữ liệu đầu vào

## Ví dụ sử dụng
```php
// Tạo mới campaign
$campaign = $campaignService->create([
    'name' => 'Chiến dịch test cầu lô',
    'description' => 'Test chiến lược cầu lô mới',
    'start_date' => '2024-06-01'
]);

// Thêm lượt đặt cược
$campaignService->addBet($campaign->id, [
    'bet_date' => '2024-06-02',
    'bet_numbers' => [18, 25, 36],
    'bet_amount' => 30000
]);

// Tính toán hiệu quả
$report = $campaignService->getReport($campaign->id);
```

## Lưu ý
- Nên validate dữ liệu đầu vào khi tạo campaign/bet
- Sử dụng transaction khi thêm nhiều bet cùng lúc
- Có thể mở rộng thêm các trường như: loại chiến lược, user_id, log chi tiết
- Nên cache các thống kê để tối ưu hiệu năng

## Routes
```php
Route::prefix('campaigns')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/{campaign}/finish', [CampaignController::class, 'finish'])->name('campaigns.finish');
    Route::post('/{campaign}/bets', [CampaignController::class, 'storeBet'])->name('campaigns.bets.store');
});
```

## Luồng đặt cược trong chiến dịch

### 1. Đặt cược thủ công
- Người dùng vào trang chi tiết chiến dịch (`/campaigns/{id}`), nhấn nút "Thêm cược" để mở modal nhập thông tin bet (ngày, số lô, số tiền).
- Dữ liệu gửi về route `campaigns.bets.store`, controller validate và gọi service `addBet` để lưu vào DB.
- Lịch sử cược hiển thị ngay trên giao diện, gồm: ngày, số lô, tiền cược, kết quả, lợi nhuận.
- Kết quả và lợi nhuận sẽ được cập nhật tự động sau khi có kết quả xổ số (bởi job tự động).

### 2. Đặt cược tự động
- Khi nhấn nút "Chạy" hoặc theo lịch, hệ thống sẽ tự động chọn số lô theo chiến lược (ví dụ: lấy insight heatmap, top streak, v.v).
- Job `CampaignRunJob` sẽ tạo bet mới, lưu vào DB, gửi thông báo nếu cần.
- Sau khi có kết quả xổ số, job `CampaignResultJob` sẽ tự động kiểm tra các bet chưa có kết quả, cập nhật trường `result` và `profit`.
- Service sẽ tự động cập nhật lại thống kê tổng lợi nhuận, tỷ lệ thắng cho campaign.
- Lịch sử cược sẽ tự động hiển thị kết quả và lợi nhuận mới nhất.

#### Gợi ý mở rộng
- Có thể cấu hình schedule để job tự động chạy mỗi ngày (xem `app/Console/Kernel.php`).
- Cho phép user chọn chiến lược auto khi tạo campaign (bổ sung trường vào form, truyền vào job).
- Bổ sung thông báo khi có bet mới/kết quả mới.
- Hiển thị chi tiết hơn về từng bet (số lô trúng, phân tích hiệu quả, v.v).

---
*Xem chi tiết code trong file `app/Services/CampaignService.php` để biết thêm các hàm phụ trợ và logic xử lý.* 