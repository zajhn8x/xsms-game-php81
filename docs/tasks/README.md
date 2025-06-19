# Tasks Documentation

Thư mục này chứa các tài liệu hướng dẫn thực hiện các task phổ biến trong hệ thống phân tích xổ số.

## Cấu trúc Tasks

### 1. Data Management (Quản lý dữ liệu)
- [Import dữ liệu xổ số](./data-management/import-lottery-data.md)
- [Backup và restore database](./data-management/backup-restore.md)
- [Kiểm tra và làm sạch dữ liệu](./data-management/data-validation.md)

### 2. Development (Phát triển)
- [Setup môi trường phát triển](./development/setup-environment.md)
- [Tạo migration mới](./development/create-migration.md)
- [Tạo service mới](./development/create-service.md)
- [Viết test cases](./development/writing-tests.md)

### 3. Analysis (Phân tích)
- [Chạy phân tích heatmap](./analysis/run-heatmap-analysis.md)
- [Tạo và kiểm tra formula](./analysis/formula-management.md)
- [Mô phỏng chiến dịch](./analysis/campaign-simulation.md)

### 4. Maintenance (Bảo trì)
- [Tối ưu hóa database](./maintenance/database-optimization.md)
- [Monitoring và logging](./maintenance/monitoring.md)
- [Troubleshooting thường gặp](./maintenance/troubleshooting.md)

### 5. Deployment (Triển khai)
- [Deploy lên production](./deployment/production-deployment.md)
- [Rollback version](./deployment/rollback.md)
- [Zero-downtime deployment](./deployment/zero-downtime.md)

### 6. Betting System (Hệ thống đặt cược)
- [Tổng quan hệ thống đặt cược nhiều người dùng](./betting-system/README.md)
- [Đặt cược thử nghiệm với dữ liệu quá khứ](./betting-system/betting/historical-testing.md)
- [Phân quyền người dùng](./betting-system/user-management/user-roles-permissions.md)
- [Tạo và cấu hình chiến dịch](./betting-system/campaign-management/create-campaign.md)
- [Hệ thống ví điện tử](./betting-system/financial/wallet-system.md)
- [Dashboard tổng quan](./betting-system/analytics/dashboard.md)

## Quy tắc viết Task

1. **Tiêu đề rõ ràng**: Mô tả ngắn gọn mục đích của task
2. **Prerequisites**: Liệt kê các yêu cầu trước khi thực hiện
3. **Steps**: Các bước thực hiện chi tiết và có thứ tự
4. **Verification**: Cách kiểm tra task đã hoàn thành thành công
5. **Troubleshooting**: Các lỗi thường gặp và cách xử lý
6. **References**: Liên kết đến tài liệu liên quan

## Sử dụng Tasks

Mỗi task được thiết kế để:
- Có thể thực hiện độc lập
- Có thể lặp lại
- Có thể kiểm tra kết quả
- Có hướng dẫn xử lý lỗi

## Đóng góp

Khi thêm task mới:
1. Tạo file markdown trong thư mục phù hợp
2. Tuân thủ template chuẩn
3. Cập nhật README này
4. Test task trước khi commit 
