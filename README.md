# Hệ Thống Phân Tích Heatmap & Cầu Lô

## Tổng quan
Hệ thống phân tích và theo dõi các cầu lô dựa trên dữ liệu heatmap, mô phỏng chiến lược chơi, và thống kê kết quả xổ số.

## Định nghĩa nghiệp vụ
- **Lô**: Hai chữ số cuối của mỗi giải trong kết quả xổ số. Mỗi ngày có 27 con lô.
- **Cầu Lô**: Cặp số được tạo bằng cách ghép các chữ số tại các vị trí cụ thể trong bảng kết quả xổ số (VD: DB-1 và G7-4-2 → 18).
- **Cấu trúc vị trí**: DB-1, G1-1, G2-1-1, G3-1-1, G7-1-1, ...
- **Cách ghép cầu**: Chọn 2 vị trí, lấy số, ghép lại, kiểm tra xuất hiện ở 27 lô ngày hôm sau.
- **Thông tin cần lưu cho mỗi cầu**: position_1, position_2, ngay_bat_dau, so_ngay_chay, so_lan_trung, ty_le_trung, streak.

## Cài đặt & Cấu hình
- Framework: Laravel v10.x
- Database: MySQL
- PHP: >=8.1
- Cache: Redis (khuyến nghị)

### Yêu cầu hệ thống
- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Redis (tùy chọn, cho cache)

### Cài đặt
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## Cấu trúc Service

### 1. Domain Services
- **LotteryResultService**: Quản lý kết quả xổ số
- **LotteryIndexResultsService**: Phân tích dữ liệu theo vị trí
- **FormulaHitService**: Phân tích cầu lô và tính toán streak
- **HeatmapInsightService**: Phân tích và lưu trữ insight

### 2. Query Services
- **HeatmapInsightQueryService**: Truy vấn dữ liệu insight
- **FormulaHitQueryService**: Truy vấn dữ liệu hit
- **LotteryResultQueryService**: Truy vấn kết quả xổ số

### 3. Command Services
- **HeatmapInsightCommandService**: Tạo và cập nhật insight
- **FormulaHitCommandService**: Tạo và cập nhật hit
- **LotteryResultCommandService**: Tạo và cập nhật kết quả

## Các Route Chính

### 1. Timeline
```
GET /timeline/{id}
```
- Hiển thị timeline của cầu lô
- Phân tích streak và tỷ lệ hit
- Thống kê 500 ngày gần nhất

### 2. Heatmap
```
GET /heatmap
```
- Phân tích 30 ngày gần nhất
- Theo dõi streak và hiệu suất
- Lọc cầu lô nổi bật

### 3. Heatmap Analytic
```
GET /heatmap/analytic
```
- Phân tích insight theo loại:
  - Long Run
  - Rebound After Long Run
  - Long Run Stop
- Lọc và tìm kiếm nâng cao

## Quy tắc Phát Triển

### 1. Cấu trúc Service
- Tách biệt Command và Query (CQRS)
- Sử dụng Repository pattern
- Cache khi cần thiết

### 2. Tối ưu Performance
- Sử dụng index cho các cột thường xuyên tìm kiếm
- Cache kết quả truy vấn phức tạp
- Eager loading cho relationships

### 3. Code Style
- PSR-12
- Type hinting
- Docblock đầy đủ

## Tài liệu Chi tiết
- [LotteryResultService](docs/services/LotteryResultService.md)
- [LotteryIndexResultsService](docs/services/LotteryIndexResultsService.md)
- [FormulaHitService](docs/services/FormulaHitService.md)
- [HeatmapInsightService](docs/services/HeatmapInsightService.md)

## Contributing
1. Fork repository
2. Tạo branch mới
3. Commit changes
4. Push to branch
5. Tạo Pull Request

## License
MIT License
