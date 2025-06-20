# **Hướng Dẫn Thiết Kế & Triển Khai Giao Diện Người Dùng (UI) Chuẩn Tiện Ích với Tailwind CSS**

## **1\. Phân tách chức năng (Component Scope)**

* ✅ Nên: Chỉ xử lý logic hiển thị trong component UI, import dữ liệu từ module khác.  
* ❌ Không nên: Nhúng logic nghiệp vụ hoặc thao tác dữ liệu trong hàm hiển thị UI.  
* ✅ Nên: Không tạo vòng lặp phụ thuộc giữa các module.

Ví dụ:

*1// Hiển thị thông tin task (chỉ UI)*  
*2*function displayTaskInfo(task) {  
*3*  *// Chỉ dùng cho hiển thị*  
*4*  console.log(\`Task: \#${task.id} \- ${task.title}\`);

*5*}

---

## **2\. Quy chuẩn màu sắc & khung hiển thị**

* Thông báo: text-blue-600  
* Thành công: text-green-600, border-green-500  
* Cảnh báo: text-yellow-600, border-yellow-500  
* Lỗi: text-red-600, border-red-500  
* Nhấn mạnh: text-cyan-600, border-cyan-500  
* Subtask: text-pink-600

Box thông báo thành công:

*1*\<div class\="rounded-lg border-2 border-green-500 bg-green-50 p-4 mb-4 text-green-700 font-semibold"\>  
*2*  Tác vụ đã hoàn thành thành công\!

*3*\</div\>

---

## **3\. Hiển thị bảng dữ liệu**

* Sử dụng: table-auto, w-full, border, text-left, font-bold cho header.  
* Màu header: bg-cyan-100 \+ text-cyan-700.  
* Đảm bảo: Cột đủ rộng; dữ liệu dễ đọc.

Ví dụ:

*1*\<table class\="table-auto w-full border-collapse my-4"\>  
*2*  \<thead\>  
*3*    \<tr class\="bg-cyan-100 text-cyan-700 font-bold"\>  
*4*      \<th class\="px-4 py-2 w-16"\>ID\</th\>  
*5*      \<th class\="px-4 py-2 w-64"\>Tiêu đề\</th\>  
*6*      \<th class\="px-4 py-2 w-32"\>Trạng thái\</th\>  
*7*      \<th class\="px-4 py-2 w-20"\>Ưu tiên\</th\>  
*8*      \<th class\="px-4 py-2 w-40"\>Phụ thuộc\</th\>  
*9*    \</tr\>  
*10*  \</thead\>  
*11*  \<tbody\>  
*12*    *\<\!-- Dữ liệu task \--\>*  
*13*  \</tbody\>

*14*\</table\>

---

## **4\. Loading Indicator (Trạng thái đang xử lý)**

* Dùng: animate-spin, text-blue-600, thông báo đi kèm.  
* Dừng hiệu ứng khi hoàn tất hoặc lỗi.

Ví dụ:

*1*\<div class\="flex items-center space-x-2"\>  
*2*  \<svg class\="animate-spin h-5 w-5 text-blue-600" viewBox\="0 0 24 24"\>*\<\!-- icon \--\>*\</svg\>  
*3*  \<span\>Đang xử lý dữ liệu...\</span\>

*4*\</div\>

---

## **5\. Hàm hỗ trợ & Định dạng trạng thái**

* Nên dùng: getStatusWithColor, truncateText, formatDependencies cho hiển thị đồng nhất.  
* Trạng thái: "Đang làm" màu vàng, "Hoàn thành" màu xanh.

---

## **6\. Báo cáo tiến độ**

* Hiển thị: Số lượng đã hoàn thành, thanh tiến độ (bg-cyan-200, bg-cyan-600 cho phần hoàn thành).  
* Có cả: Số lượng & phần trăm.

Ví dụ:

*1*\<div\>  
*2*  \<div class\="mb-1 text-cyan-700"\>Tiến độ: 3/5 (60%)\</div\>  
*3*  \<div class\="w-full bg-cyan-200 rounded-full h-2.5"\>  
*4*    \<div class\="bg-cyan-600 h-2.5 rounded-full" style\="width: 60%"\>\</div\>  
*5*  \</div\>

*6*\</div\>

---

## **7\. Gợi ý thao tác tiếp theo**

* Sau mỗi thao tác: Hiển thị box hướng dẫn, màu nhấn (border-cyan-500, bg-cyan-50).

Ví dụ:

*1*\<div class\="rounded-lg border-2 border-cyan-500 bg-cyan-50 p-4 my-4"\>  
*2*  \<span class\="font-bold text-cyan-700"\>Bước tiếp theo:\</span\>  
*3*  \<div class\="mt-2"\>  
*4*    \<span class\="text-cyan-600"\>1.\</span\> Chạy \<span class\="text-yellow-600"\>task-master list\</span\> để xem tất cả task\<br\>  
*5*    \<span class\="text-cyan-600"\>2.\</span\> Chạy \<span class\="text-yellow-600"\>task-master show \--id=...\</span\> để xem chi tiết  
*6*  \</div\>

*7*\</div\>

---

## **8\. Phân tích chi tiết (Token breakdown, code highlight, multi-section)**

* Box riêng: border-cyan-500, border-gray-400 cho từng loại.  
* Highlight code: bg-gray-900 text-green-300 font-mono overflow-x-auto p-4 rounded-lg.  
* Hiển thị: Nguồn dữ liệu, số ký tự, số token.

Ví dụ (code block):

*1*\<pre class\="bg-gray-900 text-green-300 font-mono overflow-x-auto p-4 rounded-lg mb-4"\>  
*2*\<code\>// Mã nguồn hoặc kết quả AI trả về\</code\>

*3*\</pre\>

---

## **9\. Lưu ý tích hợp & tham khảo**

* Tách biệt UI và business logic.

