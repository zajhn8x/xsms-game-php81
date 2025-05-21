# Hệ Thống Phân Tích Heatmap & Cầu Lô

## Tổng quan
Hệ thống phân tích và theo dõi các cầu lô dựa trên dữ liệu heatmap, mô phỏng chiến lược chơi, và thống kê kết quả xổ số.

## Định nghĩa nghiệp vụ
- **Lô**: Hai chữ số cuối của mỗi giải trong kết quả xổ số. Mỗi ngày có 27 con lô.
- **Cầu Lô**: Cặp số được tạo bằng cách ghép các chữ số tại các vị trí cụ thể trong bảng kết quả xổ số (VD: DB-1 và G7-4-2 → 18).
- **Cấu trúc vị trí**: DB-1, G1-1, G2-1-1, G3-1-1, G7-1-1, ...
- **Cách ghép cầu**: Chọn 2 vị trí, lấy số, ghép lại, kiểm tra xuất hiện ở 27 lô ngày hôm sau.
- **Thông tin cần lưu cho mỗi cầu**: position_1, position_2, ngay_bat_dau, so_ngay_chay, so_lan_trung, ty_le_trung, streak.

## Cài đặt & cấu trúc thư mục
- Framework: Laravel v10.x
- Database: MySQL
- PHP: >=8.1

### Cấu trúc tài liệu
- README.md (tổng quan, định nghĩa, hướng dẫn)
- readme.LotteryResultService.md
- readme.LotteryIndexResultsService.md
- readme.FormulaHitService.md
- ...

## Mô tả chức năng từng service
- **LotteryResultService**
  - Quản lý, truy vấn, lưu trữ kết quả xổ số từng ngày.
  - Chỉ làm việc với bảng LotteryResult, không xử lý logic cầu lô.
- **LotteryIndexResultsService**
  - Truy vấn, phân tích dữ liệu từng vị trí số (theo index: DB-1, G1-1, ...).
  - Hỗ trợ lấy giá trị các cặp số, kiểm tra dữ liệu đầy đủ, truy vấn lịch sử vị trí.
- **FormulaHitService**
  - Phân tích cầu lô, tính streak, sinh heatmap, thống kê timeline, tìm các cầu lô trúng liên tiếp.
  - Sinh danh sách lô từ kết quả công thức, phục vụ cho các phân tích sâu hơn.
- **HeatmapInsightService**
  - Phân tích dữ liệu heatmap, phát hiện và lưu insight về các cầu lô (long_run, long_run_stop, rebound_after_long_run).
  - Phục vụ đánh giá, tối ưu chiến lược chơi, và cung cấp dữ liệu cho analytic.
- **CauLoStrategyService** (nếu có)
  - Mô phỏng, đánh giá các chiến lược chọn cầu lô tối ưu dựa trên dữ liệu lịch sử và các chỉ số phân tích.
- **CauLoSimulationService** (nếu có)
  - Mô phỏng kết quả khi áp dụng các cầu lô theo từng chiến lược, giúp kiểm thử hiệu quả thực tế.

## Cấu trúc các route chính

### 1. `timeline/{id}`
- **Đầu vào:** 1 id (cầu lô)
- **Chức năng:** Hiển thị timeline các cặp số gợi ý cho cầu lô này, thể hiện rõ từng ngày có hit hay không, tổng số lần hit, tỷ lệ hit trên 500 ngày gần nhất.
- **Luồng xử lý:** Sử dụng `FormulaHitService->getTimelineData` để lấy dữ liệu timeline, kết hợp kết quả xổ số, index, và lịch sử hit.

### 2. `heatmap`
- **Đầu vào:** Không (hoặc có thể truyền ngày kết thúc)
- **Chức năng:** Xét trong 30 ngày gần nhất, lấy các cầu lô có streak lớn nhất, đồng thời theo dõi các cầu lô đó trong 30 ngày trước đó để phân tích chuỗi streak và hiệu suất.
- **Luồng xử lý:** Sử dụng `FormulaHitService->getHeatMap` để sinh dữ liệu heatmap, lọc các cầu lô nổi bật theo streak.

### 3. `heatmap-analytic`
- **Đầu vào:** Không (hoặc có thể truyền ngày/filter)
- **Chức năng:** Dựa trên dữ liệu heatmap đã sinh, chọn lọc và lưu insight các cầu lô theo các nhóm: long_run, rebound_after_long_run, long_run_stop, ...
- **Luồng xử lý:** Sử dụng `HeatmapInsightService` để phân tích, chọn lọc, và lưu insight vào DB. Giao diện analytic cho phép lọc, phân tích sâu theo từng loại insight.

## Tài liệu chi tiết từng service
- [LotteryResultService](readme.LotteryResultService.md)
- [LotteryIndexResultsService](readme.LotteryIndexResultsService.md)
- [FormulaHitService](readme.FormulaHitService.md)
- [HeatmapInsightService](readme.HeatmapInsightService.md)

---
*Xem chi tiết từng file readme.[ServiceName].md để biết rõ chức năng, ví dụ sử dụng và lưu ý nghiệp vụ từng service.*
