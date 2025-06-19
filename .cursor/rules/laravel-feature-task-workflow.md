**Tên quy tắc: laravel-feature-task-workflow.md**

### **Mô tả:**

Hướng dẫn quy trình làm việc phát triển Laravel dựa trên task, tập trung vào việc tổ chức công việc theo tính năng hoặc nhánh mà không sử dụng khái niệm "master task" trung tâm.

# ---

**Quy Trình Phát Triển Laravel Dựa trên Task theo Tính năng/Nhánh**

Quy tắc này cung cấp một quy trình làm việc có cấu trúc, tập trung vào việc quản lý và thực hiện các task phát triển trong dự án Laravel của bạn. Đặc điểm chính là **không sử dụng khái niệm "master task" trung tâm**. Thay vào đó, mọi task sẽ được tổ chức theo từng tính năng hoặc nhánh phát triển cụ thể, mang lại sự linh hoạt và độc lập cao hơn cho từng phần công việc.

## **Nguyên tắc cốt lõi**

* **Task/Tính năng là Đơn vị Công việc**: Mọi công việc đều được định nghĩa, thực hiện và theo dõi theo từng task hoặc nhóm task của một tính năng cụ thể.  
* **Minh bạch & Có thể theo dõi**: Đảm bảo mọi task đều có thông tin rõ ràng về mục tiêu, tiến độ và các phụ thuộc.  
* **Độc lập theo Ngữ cảnh**: Các task của một tính năng/nhánh không bị ràng buộc bởi một danh sách "master" tổng thể, giúp giảm thiểu xung đột.  
* **Tận dụng Công cụ Hiện có**: Tối đa hóa việc sử dụng các tính năng của Cursor, Composer, Artisan và Git.  
* **AI làm Trợ lý**: Sử dụng AI để hỗ trợ lập kế hoạch, sinh mã, tái cấu trúc và gỡ lỗi ở cấp độ task.

## **Chu trình Phát triển dựa trên Task (theo Tính năng/Nhánh)**

Đây là một vòng lặp liên tục bạn sẽ tuân theo cho mỗi tính năng hoặc sửa lỗi:

1. **Định nghĩa Task & Ngữ cảnh**: Xác định các task/subtask cần thực hiện và quyết định ngữ cảnh (ví dụ: tên tính năng, tên nhánh Git) mà chúng thuộc về.  
2. **Lập kế hoạch Task**: Hiểu rõ yêu cầu, phân tích tác động và lên kế hoạch triển khai trong ngữ cảnh đã chọn.  
3. **Triển khai Task**: Viết mã, bao gồm cả các thành phần Laravel (Route, Controller, Model, View).  
4. **Kiểm thử Task**: Viết và chạy các bài kiểm thử cho logic đã triển khai.  
5. **Cập nhật Tiến độ**: Ghi lại các phát hiện, tiến độ và bất kỳ thay đổi nào trong kế hoạch vào task của ngữ cảnh đó.  
6. **Review & Hoàn thành**: Đảm bảo task được review, phê duyệt và hoàn thành trong ngữ cảnh của nó.

## ---

**Chi tiết các Bước thực hiện**

### **1\. Định nghĩa Task & Ngữ cảnh (Task & Context Definition)**

Mỗi tính năng hoặc sửa lỗi lớn nên được chia thành các task nhỏ hơn, quản lý được và được đặt trong một ngữ cảnh cụ thể (tên tính năng, tên nhánh).

* **✅ NÊN:** Bắt đầu bằng cách tạo một nhánh Git mới cho tính năng/sửa lỗi (ví dụ: git checkout \-b feature/user-profile). Tên nhánh này sẽ đồng thời là tên ngữ cảnh cho các task của bạn.  
* **✅ NÊN:** Tạo một file Markdown riêng biệt cho các task của tính năng này (ví dụ: docs/tasks/feature-user-profile.md). Đây là nơi bạn sẽ định nghĩa và theo dõi các task/subtask của tính năng đó.  
* **✅ NÊN:** Cấu trúc file Markdown của bạn với một "task cha" mô tả mục tiêu tổng thể của tính năng, và chia nhỏ thành các "subtask" cụ thể, có thể thực hiện được.

**Ví dụ cấu trúc file docs/tasks/feature-user-profile.md:**

Markdown

\# Danh sách Task Tính năng: Hồ sơ Người dùng

\#\# 🎯 Task Cha: 1\. Triển khai Quản lý Hồ sơ Người dùng (Pending)

\#\#\# Mục tiêu:  
Cho phép người dùng xem và chỉnh sửa thông tin hồ sơ của họ.

\#\#\# Subtasks:

\* **\*\*1.1. Thiết lập Database Migration (Pending)\*\***  
    \* Mô tả: Tạo migration để thêm các trường hồ sơ (ví dụ: \`phone\_number\`, \`address\`) vào bảng \`users\` hoặc tạo bảng \`profiles\` riêng biệt.  
    \* Phụ thuộc: None  
\* **\*\*1.2. Xây dựng Route & View Hồ sơ (Pending)\*\***  
    \* Mô tả: Định nghĩa route GET/POST cho trang hồ sơ người dùng và tạo file Blade template.  
    \* Phụ thuộc: 1.1  
\* **\*\*1.3. Cập nhật User Model & Logic (Pending)\*\***  
    \* Mô tả: Cập nhật \`User\` model với các thuộc tính mới hoặc tạo \`Profile\` model, và viết logic xử lý dữ liệu hồ sơ.  
    \* Phụ thuộc: 1.1  
\* **\*\*1.4. Triển khai Validation & Controller (Pending)\*\***  
    \* Mô tả: Viết Form Request và logic Controller để xử lý việc xem/cập nhật hồ sơ.  
    \* Phụ thuộc: 1.2, 1.3  
\* **\*\*1.5. Viết Feature Tests (Pending)\*\***  
    \* Mô tả: Viết các bài kiểm thử end-to-end cho luồng quản lý hồ sơ.  
    \* Phụ thuộc: 1.4

\---

### **2\. Lập kế hoạch Task (Task Planning)**

Trước khi viết code, hãy hiểu rõ task và tác động của nó trong ngữ cảnh tính năng/nhánh hiện tại.

* **✅ NÊN:** Luôn làm việc trên nhánh Git của tính năng đó (ví dụ: feature/user-profile).  
* **✅ NÊN:** Đọc kỹ mô tả task và các subtask liên quan trong file Markdown của tính năng. Xác định các file cần thay đổi, các class/method mới cần tạo.  
* **✅ NÊN:** **Sử dụng Cursor Chat/AI @ command** để hỗ trợ lập kế hoạch:  
  * Dán mô tả task vào chat và hỏi: "Hãy liệt kê các file Laravel tôi có thể cần tạo/chỉnh sửa cho task này trong ngữ cảnh tính năng user-profile."  
  * Nếu task liên quan đến một phần code hiện có, chọn đoạn code đó và dùng @ command: "Đề xuất cách triển khai tính năng X trong đoạn code này."  
  * Hỏi AI về các lựa chọn thiết kế, pattern phù hợp với Laravel (ví dụ: "Tôi nên dùng Service Class hay Action Class cho logic này?").

### **3\. Triển khai Task (Task Implementation)**

Thực hiện từng subtask một cách tuần tự, ưu tiên các subtask không có phụ thuộc hoặc đã hoàn thành các phụ thuộc. Mỗi subtask sẽ được triển khai trong ngữ cảnh của nhánh/tính năng hiện tại.

#### **Luồng triển khai điển hình cho một Subtask:**

1. **Database Migrations:**  
   * **✅ NÊN:** Tạo migration ngay lập tức khi cấu trúc DB thay đổi: php artisan make:migration add\_profile\_fields\_to\_users\_table  
   * **✅ NÊN:** Sau khi viết migration, chạy php artisan migrate để cập nhật DB cục bộ.  
2. **Models:**  
   * **✅ NÊN:** Tạo Model bằng Artisan nếu cần (ví dụ: php artisan make:model Profile).  
   * **✅ NÊN:** Định nghĩa $fillable hoặc $guarded để chống Mass Assignment.  
   * **✅ NÊN:** Thêm các mối quan hệ (relationships) cần thiết (hasMany, belongsTo, v.v.).  
3. **Routes:**  
   * **✅ NÊN:** Định nghĩa route trong routes/web.php hoặc routes/api.php của bạn.  
   * **✅ NÊN:** Dùng Route::resource() cho các CRUD điển hình.  
   * **✅ NÊN:** Dùng Route::name() để đặt tên cho route.  
4. **Form Requests (Validation):**  
   * **✅ NÊN:** Tạo Form Request: php artisan make:request UpdateProfileRequest  
   * **✅ NÊN:** Viết các validation rules chi tiết trong phương thức rules().  
   * **✅ NÊN:** Đảm bảo phương thức authorize() trả về true hoặc logic kiểm tra quyền truy cập.  
5. **Controllers:**  
   * **✅ NÊN:** Tạo Controller: php artisan make:controller ProfileController.  
   * **✅ NÊN:** Dùng dependency injection để inject Form Request hoặc Service Class.  
   * **❌ KHÔNG NÊN:** Viết logic nghiệp vụ phức tạp trực tiếp trong Controller.  
6. **Service/Action Classes (nếu cần):**  
   * **✅ NÊN:** Tạo các class này trong thư mục app/Services hoặc app/Actions để chứa logic nghiệp vụ phức tạp.  
   * **✅ NÊN:** Inject các class này vào Controller hoặc các Service khác.  
7. **Views (Blade):**  
   * **✅ NÊN:** Tạo hoặc cập nhật các file Blade trong resources/views.  
   * **✅ NÊN:** Sử dụng Blade Components để tái sử dụng các phần UI.  
   * **❌ KHÔNG NÊN:** Viết logic nghiệp vụ trong Blade.

#### **Sử dụng AI trong triển khai:**

* **Tạo Boilerplate**: Dùng Cursor @ command (ví dụ: "@Tạo một Controller với các phương thức resource cho Profile model") để sinh ra code cấu trúc ban đầu.  
* **Giải thích code**: Khi đọc mã của người khác hoặc mã cũ, chọn đoạn code và hỏi Cursor: "@Giải thích đoạn code này làm gì."  
* **Hoàn thành code**: Khi bạn gõ dở một đoạn code, Cursor sẽ tự động đề xuất hoàn thành.  
* **Gỡ lỗi**: Khi gặp lỗi, dán thông báo lỗi vào Cursor Chat và hỏi: "Lỗi này nghĩa là gì và cách khắc phục?"

### **4\. Kiểm thử Task (Task Testing)**

Mọi task đều cần được kiểm thử để đảm bảo chất lượng trong ngữ cảnh của tính năng/nhánh hiện tại.

* **✅ NÊN:** Tạo Feature Test cho các endpoint: php artisan make:test UserProfileTest \--feature  
* **✅ NÊN:** Tạo Unit Test cho các Model, Service Class hoặc các hàm tiện ích: php artisan make:test ProfileServiceTest \--unit  
* **✅ NÊN:** Sử dụng Factories để tạo dữ liệu test giả mạo một cách nhất quán.  
* **✅ NÊN:** Kiểm tra cả trường hợp thành công (status 200/201) và thất bại (status 422/403/404).  
* **✅ NÊN:** Chạy các bài kiểm thử liên quan đến task của bạn:  
  * php artisan test \--filter UserProfileTest  
  * php artisan test (chạy tất cả các bài kiểm thử)  
* **Sử dụng AI trong kiểm thử:**  
  * Dán code của một phương thức và yêu cầu Cursor: "@Viết một Unit Test cho phương thức này."  
  * Khi test thất bại, dán lỗi test vào chat và hỏi Cursor để được hỗ trợ gỡ lỗi.

### **5\. Cập nhật Tiến độ (Progress Update)**

Ghi lại những gì bạn đã làm, những gì bạn đã học và bất kỳ vấn đề nào phát sinh **trực tiếp trong file Markdown của task/tính năng đó**.

* **✅ NÊN:** Cập nhật file Markdown của tính năng (ví dụ: docs/tasks/feature-user-profile.md) để phản ánh trạng thái của subtask (ví dụ: từ "Pending" sang "In Progress", hoặc "Done").  
* **✅ NÊN:** Ghi chú lại các phát hiện quan trọng vào mục "Ghi chú" hoặc "Tiến độ" trong chính subtask đó:  
  * Những thay đổi bất ngờ trong yêu cầu.  
  * Những giải pháp thay thế đã thử và lý do tại sao chúng không hoạt động.  
  * Những "sự thật cơ bản" mới được khám phá về hệ thống.  
  * Các quyết định thiết kế quan trọng.  
* **Ví dụ cập nhật trong docs/tasks/feature-user-profile.md:**  
  Markdown  
  \* **\*\*1.1. Thiết lập Database Migration (Done)\*\***  
      \* Mô tả: Tạo migration để thêm các trường hồ sơ (ví dụ: \`phone\_number\`, \`address\`) vào bảng \`users\` hoặc tạo bảng \`profiles\` riêng biệt.  
      \* Phụ thuộc: None  
      \* Ghi chú: Đã tạo \`2025\_01\_01\_123456\_add\_profile\_fields\_to\_users\_table.php\`. Chạy \`php artisan migrate\`.

### **6\. Review & Hoàn thành (Review & Completion)**

Khi tất cả các subtask trong một tính năng/nhánh đã hoàn thành, hãy tiến hành review và hoàn tất.

* **✅ NÊN:** Trước khi tạo Pull Request, hãy tự review code của mình. Đảm bảo mã tuân thủ các quy tắc định dạng (sử dụng php-cs-fixer hoặc pint).  
* **✅ NÊN:** Đảm bảo tất cả các bài kiểm thử liên quan đều vượt qua.  
* **✅ NÊN:** Tạo một Git Commit với thông điệp rõ ràng, tóm tắt công việc đã làm và ID của task/tính năng liên quan (ví dụ: feat: Implement user profile management \[Feature: User Profile\]).  
* **✅ NÊN:** Tạo Pull Request và yêu cầu người khác review. Cung cấp đầy đủ ngữ cảnh trong mô tả PR, bao gồm các link đến file Markdown của tính năng (ví dụ: docs/tasks/feature-user-profile.md).  
* **✅ NÊN:** Sau khi Pull Request được merge, cập nhật trạng thái của task cha trong file Markdown của tính năng thành "Done". Xóa nhánh Git của tính năng đó.

---