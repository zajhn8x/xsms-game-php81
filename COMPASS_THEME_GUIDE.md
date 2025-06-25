# Hướng dẫn sử dụng Compass Theme cho XSMB Game

## Giới thiệu

Compass Theme là một hệ thống thiết kế (Design System) được xây dựng dành riêng cho XSMB Game Platform. Theme này cung cấp các component UI nhất quán, hiện đại và dễ sử dụng dựa trên Tailwind CSS.

## Đặc điểm chính

### 1. **Màu sắc chủ đạo**
- **Primary**: Cyan/Blue gradient (#06b6d4)
- **Success**: Green (#22c55e)
- **Warning**: Yellow (#eab308)
- **Error**: Red (#ef4444)
- **Info**: Blue (#3b82f6)

### 2. **Typography**
- Font chính: Inter
- Các cấp độ kích thước từ xs đến 4xl
- Font weight từ 400 đến 700

### 3. **Components sẵn có**
- Cards
- Buttons
- Forms
- Tables
- Badges
- Alerts
- Progress bars
- Loading states
- Modal
- Navigation

## Cách sử dụng

### 1. **Cards**

```blade
<!-- Basic Card -->
<div class="compass-card">
    <div class="compass-card-header">
        <h3 class="text-lg font-semibold">Tiêu đề</h3>
    </div>
    <div class="compass-card-body">
        <p>Nội dung card</p>
    </div>
    <div class="compass-card-footer">
        <button class="compass-btn-primary">Hành động</button>
    </div>
</div>

<!-- Stat Card -->
<div class="compass-stat-card">
    <div class="compass-stat-value">1,234</div>
    <div class="compass-stat-label">Tổng chiến dịch</div>
    <div class="compass-stat-change-positive">
        <svg class="w-4 h-4 mr-1">...</svg>
        +12.5%
    </div>
</div>
```

### 2. **Buttons**

```blade
<!-- Primary Button -->
<button class="compass-btn-primary">Primary</button>

<!-- Secondary Button -->
<button class="compass-btn-secondary">Secondary</button>

<!-- Success Button -->
<button class="compass-btn-success">Success</button>

<!-- Danger Button -->
<button class="compass-btn-danger">Danger</button>

<!-- Button với Icon -->
<button class="compass-btn-primary">
    <svg class="w-4 h-4 mr-2">...</svg>
    Tạo mới
</button>
```

### 3. **Forms**

```blade
<form>
    <!-- Input -->
    <div>
        <label class="compass-label">Nhãn</label>
        <input type="text" class="compass-input" placeholder="Placeholder">
    </div>
    
    <!-- Select -->
    <div>
        <label class="compass-label">Lựa chọn</label>
        <select class="compass-select">
            <option>Option 1</option>
            <option>Option 2</option>
        </select>
    </div>
    
    <!-- Textarea -->
    <div>
        <label class="compass-label">Mô tả</label>
        <textarea class="compass-textarea" rows="3"></textarea>
    </div>
</form>
```

### 4. **Tables**

```blade
<div class="compass-table">
    <table class="w-full">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Item 1</td>
                <td><span class="compass-badge-success">Active</span></td>
            </tr>
        </tbody>
    </table>
</div>
```

### 5. **Alerts**

```blade
<!-- Success Alert -->
<div class="compass-alert-success">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-2">...</svg>
        Thao tác thành công!
    </div>
</div>

<!-- Warning Alert -->
<div class="compass-alert-warning">
    Cảnh báo nội dung
</div>

<!-- Error Alert -->
<div class="compass-alert-error">
    Có lỗi xảy ra
</div>

<!-- Info Alert -->
<div class="compass-alert-info">
    Thông tin hữu ích
</div>
```

### 6. **Badges**

```blade
<span class="compass-badge-primary">Primary</span>
<span class="compass-badge-success">Success</span>
<span class="compass-badge-warning">Warning</span>
<span class="compass-badge-error">Error</span>
```

### 7. **Progress Bars**

```blade
<div class="compass-progress">
    <div class="compass-progress-bar" style="width: 65%"></div>
</div>
```

### 8. **Loading States**

```blade
<!-- Spinner -->
<div class="compass-spinner"></div>

<!-- Skeleton -->
<div class="compass-skeleton h-4 w-3/4"></div>
```

## Lottery Specific Components

### Lottery Balls

```blade
<div class="lottery-ball">01</div>
<div class="lottery-ball">15</div>
<div class="lottery-ball">23</div>
```

### Campaign Status

```blade
<span class="campaign-status-active">Đang chạy</span>
<span class="campaign-status-paused">Tạm dừng</span>
<span class="campaign-status-completed">Hoàn thành</span>
```

## Animations

### Fade In
```blade
<div class="compass-fade-in">
    Nội dung fade in
</div>
```

### Slide Up
```blade
<div class="compass-slide-up">
    Nội dung slide up
</div>
```

### Bounce
```blade
<div class="compass-bounce">
    Nội dung bounce
</div>
```

## Utility Classes

### Text Gradient
```blade
<h1 class="compass-text-gradient">Tiêu đề gradient</h1>
```

### Background Gradient
```blade
<div class="compass-bg-gradient p-4 text-white">
    Nội dung với background gradient
</div>
```

### Shadow Glow
```blade
<div class="compass-shadow-glow">
    Element với shadow glow
</div>
```

## Best Practices

1. **Sử dụng components nhất quán**: Luôn sử dụng các class Compass thay vì tự viết CSS
2. **Kết hợp với Tailwind**: Có thể kết hợp các class Compass với utility classes của Tailwind
3. **Responsive**: Các components đã được thiết kế responsive, sử dụng các breakpoint của Tailwind khi cần
4. **Dark mode**: Theme hỗ trợ dark mode tự động
5. **Performance**: Các animation đã được tối ưu để chạy mượt mà

## Xem Demo

Truy cập `/compass-theme` trong môi trường development để xem toàn bộ các components.

## Tùy chỉnh

Để tùy chỉnh theme, chỉnh sửa file:
- `tailwind.config.js`: Màu sắc, font, spacing
- `resources/css/app.css`: Components và utilities

## Support

Nếu cần hỗ trợ hoặc có đề xuất cải thiện theme, vui lòng liên hệ team development. 
