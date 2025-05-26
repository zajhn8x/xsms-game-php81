# Quy tắc xây dựng Service cho hệ thống phân tích xổ số

## 1. Tách biệt rõ ràng giữa các loại service
- **Domain Service (Service nghiệp vụ):** Chỉ xử lý logic nghiệp vụ, không dính request/response.
- **Query Service:** Chỉ phục vụ việc lấy dữ liệu, tối ưu cho việc show (join, eager load, cache...).
- **Command Service:** Chỉ phục vụ việc tạo mới, cập nhật, xóa dữ liệu.

## 2. Áp dụng nguyên tắc CQRS (Command Query Responsibility Segregation)
- **Command:** Xử lý các thao tác ghi (create/update/delete), trả về kết quả thành công/thất bại.
- **Query:** Xử lý các thao tác đọc, trả về dữ liệu cho view hoặc API.

### Ví dụ:
```php
// Command Service
class HeatmapInsightCommandService {
    public function analyzeAndSave(array $heatmap) { ... }
}

// Query Service
class HeatmapInsightQueryService {
    public function getInsights(array $filter) { ... }
    public function getTopInsights($date, $type, $limit) { ... }
}
```
Controller sẽ inject đúng service theo mục đích (Command hoặc Query).

## 3. Tối ưu cho performance và maintainability
- **Caching:** Query Service có thể cache kết quả truy vấn insight, giảm tải DB.
- **Read Model riêng:** Nếu analytic phức tạp, có thể xây dựng bảng/tập dữ liệu riêng cho analytic (denormalized table).
- **Đặt tên rõ ràng:** Tên hàm, tên service phải thể hiện đúng mục đích (ví dụ: `analyzeAndSaveInsights`, `getInsightsForDate`...)

## 4. Viết test cho service
- Service càng tách biệt, càng dễ viết unit test, đảm bảo logic nghiệp vụ luôn đúng khi refactor.

## 5. Định nghĩa interface cho service
- Để dễ mock/test và mở rộng về sau.

## 6. Tài liệu hóa rõ ràng
- Viết README cho từng service, mô tả chức năng, input/output, ví dụ sử dụng, lưu ý nghiệp vụ.

---

## Kết luận
- Xây dựng service dùng chung là tốt, nhưng nên tách biệt rõ giữa xử lý ghi và đọc (CQRS).
- Tối ưu cho maintainability, performance, testability.
- Định hướng này giúp hệ thống lớn lên vẫn dễ mở rộng, dễ bảo trì, dễ kiểm soát nghiệp vụ. 