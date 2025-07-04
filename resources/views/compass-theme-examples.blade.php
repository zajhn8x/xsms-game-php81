@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-8">
    <!-- Page Header -->
    <div class="text-center mb-12 compass-fade-in">
        <h1 class="text-4xl font-bold compass-text-gradient mb-4">Compass Theme Design System</h1>
        <p class="text-gray-600 text-lg">Hệ thống thiết kế chuyên nghiệp cho XSMB Game Platform</p>
    </div>

    <!-- Cards Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Basic Card -->
            <div class="compass-card">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold">Basic Card</h3>
                </div>
                <div class="compass-card-body">
                    <p class="text-gray-600">Đây là một card cơ bản với header và body.</p>
                </div>
                <div class="compass-card-footer">
                    <button class="compass-btn-primary">Xem thêm</button>
                </div>
            </div>

            <!-- Stat Card -->
            <div class="compass-stat-card">
                <div class="compass-stat-value">1,234</div>
                <div class="compass-stat-label">Tổng chiến dịch</div>
                <div class="compass-stat-change-positive">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                    +12.5%
                </div>
            </div>

            <!-- Chart Card -->
            <div class="compass-chart-container">
                <div class="compass-chart-header">
                    <h3 class="compass-chart-title">Doanh thu</h3>

                    <!-- Custom Dropdown -->
                    <div x-data="{ open: false, selected: '7 ngày', options: ['7 ngày', '30 ngày'] }" class="compass-dropdown-wrapper">
                        <button @click="open = !open" class="compass-dropdown-button w-32">
                            <span x-text="selected"></span>
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="compass-dropdown-menu" x-transition style="display: none;">
                            <ul class="py-1">
                                <template x-for="option in options" :key="option">
                                    <li>
                                        <button @click="selected = option; open = false" class="compass-dropdown-item">
                                            <svg x-show="selected === option" class="w-4 h-4 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span :class="{'compass-dropdown-item-selected': selected === option}" x-text="option"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                    <!-- End Custom Dropdown -->
                </div>
                <div class="h-32 flex items-end justify-around">
                    <div class="w-8 bg-primary-500 rounded-t" style="height: 60%"></div>
                    <div class="w-8 bg-primary-500 rounded-t" style="height: 80%"></div>
                    <div class="w-8 bg-primary-500 rounded-t" style="height: 45%"></div>
                    <div class="w-8 bg-primary-500 rounded-t" style="height: 90%"></div>
                    <div class="w-8 bg-primary-500 rounded-t" style="height: 70%"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Buttons Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Buttons</h2>
        <div class="flex flex-wrap gap-4">
            <button class="compass-btn-primary">Primary Button</button>
            <button class="compass-btn-secondary">Secondary Button</button>
            <button class="compass-btn-success">Success Button</button>
            <button class="compass-btn-danger">Danger Button</button>

            <!-- With Icons -->
            <button class="compass-btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tạo mới
            </button>
        </div>
    </section>

    <!-- Forms Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Forms</h2>
        <div class="compass-card max-w-2xl">
            <div class="compass-card-body">
                <form class="space-y-4">
                    <div>
                        <label class="compass-label">Tên chiến dịch</label>
                        <input type="text" class="compass-input" placeholder="Nhập tên chiến dịch">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="compass-label">Loại chiến dịch</label>
                            <div x-data="customSelect({
                                    options: [
                                        { value: 'lo_xien', text: 'Lô xiên' },
                                        { value: 'lo_gan', text: 'Lô gan' },
                                        { value: 'bach_thu', text: 'Bạch thủ' }
                                    ],
                                    initialValue: 'bach_thu'
                                })"
                                 @click.outside="open = false"
                                 class="relative">
                                <button type="button" @click="open = !open" class="compass-select w-full text-left">
                                    <span x-text="selectedText"></span>
                                    <svg class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-10 mt-2 w-full bg-white rounded-md shadow-lg border border-gray-200"
                                     style="display: none;">
                                    <ul class="py-1">
                                        <template x-for="option in options" :key="option.value">
                                            <li @click="selectOption(option)"
                                                class="px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 cursor-pointer flex items-center justify-between">
                                                <span x-text="option.text"></span>
                                                <svg x-show="option.value === selectedValue" class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="compass-label">Ngân sách</label>
                            <input type="number" class="compass-input" placeholder="0" value="0">
                        </div>
                    </div>

                    <div>
                        <label class="compass-label">Mô tả</label>
                        <textarea class="compass-textarea" rows="3" placeholder="Nhập mô tả chiến dịch"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" class="compass-btn-secondary">Hủy</button>
                        <button type="submit" class="compass-btn-primary">Lưu chiến dịch</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Tables Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Tables</h2>
        <div class="compass-table">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="compass-table th">#</th>
                        <th class="compass-table th">Chiến dịch</th>
                        <th class="compass-table th">Trạng thái</th>
                        <th class="compass-table th">Tiến độ</th>
                        <th class="compass-table th">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="compass-table td">1</td>
                        <td class="compass-table td">
                            <div>
                                <div class="font-medium">Chiến dịch Lô xiên 2</div>
                                <div class="text-sm text-gray-500">Bắt đầu: 25/01/2025</div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            <span class="campaign-status-active">Đang chạy</span>
                        </td>
                        <td class="compass-table td">
                            <div class="compass-progress">
                                <div class="compass-progress-bar" style="width: 65%"></div>
                            </div>
                            <span class="text-xs text-gray-600 mt-1">65%</span>
                        </td>
                        <td class="compass-table td">
                            <button class="compass-btn-primary text-xs py-1 px-3">Chi tiết</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="compass-table td">2</td>
                        <td class="compass-table td">
                            <div>
                                <div class="font-medium">Chiến dịch Bạch thủ</div>
                                <div class="text-sm text-gray-500">Bắt đầu: 24/01/2025</div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            <span class="campaign-status-paused">Tạm dừng</span>
                        </td>
                        <td class="compass-table td">
                            <div class="compass-progress">
                                <div class="compass-progress-bar" style="width: 30%"></div>
                            </div>
                            <span class="text-xs text-gray-600 mt-1">30%</span>
                        </td>
                        <td class="compass-table td">
                            <button class="compass-btn-primary text-xs py-1 px-3">Chi tiết</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Badges & Alerts Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Badges & Alerts</h2>

        <!-- Badges -->
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-3">Badges</h3>
            <div class="flex flex-wrap gap-2">
                <span class="compass-badge-primary">Primary</span>
                <span class="compass-badge-success">Success</span>
                <span class="compass-badge-warning">Warning</span>
                <span class="compass-badge-error">Error</span>
            </div>
        </div>

        <!-- Alerts -->
        <div class="space-y-3">
            <h3 class="text-lg font-medium mb-3">Alerts</h3>
            <div class="compass-alert-success">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Chiến dịch đã được tạo thành công!
                </div>
            </div>

            <div class="compass-alert-warning">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Ngân sách chiến dịch sắp hết. Vui lòng nạp thêm.
                </div>
            </div>

            <div class="compass-alert-error">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Có lỗi xảy ra. Vui lòng thử lại sau.
                </div>
            </div>

            <div class="compass-alert-info">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Hệ thống sẽ bảo trì từ 2:00 - 4:00 sáng mai.
                </div>
            </div>
        </div>
    </section>

    <!-- Lottery Specific Components -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Lottery Components</h2>

        <!-- Lottery Balls -->
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-3">Lottery Balls</h3>
            <div class="flex flex-wrap gap-3">
                <div class="lottery-ball">01</div>
                <div class="lottery-ball">15</div>
                <div class="lottery-ball">23</div>
                <div class="lottery-ball">37</div>
                <div class="lottery-ball">42</div>
                <div class="lottery-ball">88</div>
            </div>
        </div>

        <!-- Lottery Result Card -->
        <div class="lottery-result-card max-w-md">
            <div class="compass-card-header">
                <h3 class="text-lg font-semibold">Kết quả XSMB - 25/01/2025</h3>
            </div>
            <div class="compass-card-body">
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">Giải đặc biệt:</span>
                        <div class="text-2xl font-bold text-primary-600 mt-1">12345</div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Giải nhất:</span>
                        <div class="text-xl font-semibold mt-1">67890</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Authentication Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Authentication Forms</h2>

        <!-- Login Form Example -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">Login Form</h3>
            <div class="max-w-md">
                <div class="compass-card bg-white border shadow-lg">
                    <div class="compass-card-body p-6">
                        <div class="text-center mb-6">
                            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary-100 mb-4">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Đăng nhập</h3>
                            <p class="text-gray-500">Truy cập tài khoản của bạn</p>
                        </div>

                        <form class="space-y-4">
                            <div>
                                <label class="compass-label">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                        </svg>
                                    </div>
                                    <input type="email" class="compass-input pl-10" placeholder="your@email.com">
                                </div>
                            </div>

                            <div>
                                <label class="compass-label">Mật khẩu</label>
                                <div class="relative" x-data="{ showPassword: false }">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <input :type="showPassword ? 'text' : 'password'" class="compass-input pl-10 pr-10" placeholder="••••••••">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                    <label class="ml-2 text-sm text-gray-700">Ghi nhớ</label>
                                </div>
                                <a href="#" class="text-sm text-primary-600 hover:text-primary-500">Quên mật khẩu?</a>
                            </div>

                            <button type="submit" class="compass-btn-primary w-full justify-center">
                                Đăng nhập
                            </button>

                            <!-- Social Login Divider -->
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Hoặc đăng nhập với</span>
                                </div>
                            </div>

                            <!-- Social Login Buttons -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Google Login -->
                                <a href="#"
                                   class="social-btn social-btn-google w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Google
                                </a>

                                <!-- Facebook Login -->
                                <a href="#"
                                   class="social-btn social-btn-facebook w-full inline-flex justify-center items-center px-3 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    Facebook
                                </a>
                            </div>

                            <div class="text-center">
                                <p class="text-sm text-gray-600">
                                    Chưa có tài khoản?
                                    <a href="#" class="font-medium text-primary-600 hover:text-primary-500">Đăng ký ngay</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Form Example -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">Register Form</h3>
            <div class="max-w-md">
                <div class="compass-card bg-white border shadow-lg">
                    <div class="compass-card-body p-6">
                        <div class="text-center mb-6">
                            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary-100 mb-4">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Đăng ký</h3>
                            <p class="text-gray-500">Tạo tài khoản mới</p>
                        </div>

                        <form class="space-y-4">
                            <div>
                                <label class="compass-label">Họ và tên</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" class="compass-input pl-10" placeholder="Nguyễn Văn A">
                                </div>
                            </div>

                            <div>
                                <label class="compass-label">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                        </svg>
                                    </div>
                                    <input type="email" class="compass-input pl-10" placeholder="your@email.com">
                                </div>
                            </div>

                            <div>
                                <label class="compass-label">Mật khẩu</label>
                                <div class="relative" x-data="{ showPassword: false }">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <input :type="showPassword ? 'text' : 'password'" class="compass-input pl-10 pr-10" placeholder="Tối thiểu 8 ký tự">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="text-gray-700">
                                        Tôi đồng ý với
                                        <a href="#" class="text-primary-600 hover:text-primary-500 font-medium">Điều khoản sử dụng</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="compass-btn-primary w-full justify-center">
                                Tạo tài khoản
                            </button>

                            <div class="text-center">
                                <p class="text-sm text-gray-600">
                                    Đã có tài khoản?
                                    <a href="#" class="font-medium text-primary-600 hover:text-primary-500">Đăng nhập ngay</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gradient Background Example -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">Gradient Background (Full Screen)</h3>
            <div class="compass-card bg-white border shadow-lg">
                <div class="compass-card-body p-6">
                    <div class="h-64 w-full rounded-lg compass-gradient-bg relative overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center text-white">
                                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-white/20 backdrop-blur-sm mb-4">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold mb-2">Compass Theme</h3>
                                <p class="text-white/80">Background gradient với hiệu ứng</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">compass-gradient-bg</code>
                        - Gradient background với pattern overlay
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading States -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Loading States</h2>
        <div class="flex items-center space-x-8">
            <div class="text-center">
                <div class="compass-spinner mb-2"></div>
                <p class="text-sm text-gray-600">Loading...</p>
            </div>

            <div class="flex-1 space-y-3">
                <div class="compass-skeleton h-4 w-3/4"></div>
                <div class="compass-skeleton h-4 w-full"></div>
                <div class="compass-skeleton h-4 w-5/6"></div>
            </div>
        </div>
    </section>

    <!-- Navigation Components -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Navigation Components</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <!-- Sidebar Example -->
            <div>
                <h3 class="text-lg font-medium mb-4">Sidebar</h3>
                <div class="w-full max-w-xs bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                    <div class="flex-1 flex flex-col pt-5 pb-4">
                        <div class="flex items-center flex-shrink-0 px-4">
                            <a href="#" class="flex items-center space-x-3 group">
                                <div class="relative">
                                    <div class="absolute -inset-1 bg-gradient-to-r from-primary-600 to-info-600 rounded-xl blur-lg opacity-75 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="relative bg-gray-800 p-3 rounded-xl shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-info-600 bg-clip-text text-transparent">XSMB Game</span>
                                    <p class="text-xs text-gray-500 -mt-1">Phân tích thông minh</p>
                                </div>
                            </a>
                        </div>
                        <nav class="mt-8 flex-1 px-2 space-y-2">
                            <a href="#" class="sidebar-link sidebar-link-active">
                                <svg class="mr-4 flex-shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span>Dashboard</span>
                            </a>
                            <a href="#" class="sidebar-link">
                                <svg class="mr-4 flex-shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Kết quả xổ số</span>
                            </a>
                        </nav>
                    </div>
                    <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                        <div class="w-full" x-data="{ open: true }">
                            <div @click="open = !open" class="flex-shrink-0 w-full group block cursor-pointer">
                                <div class="flex items-center">
                                    <div>
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-info-500 rounded-full flex items-center justify-center shadow-md">
                                            <span class="text-white text-sm font-semibold">T</span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-700">Tung Anh Nguyen</p>
                                        <p class="text-xs font-medium text-gray-500">Xem hồ sơ & Cài đặt</p>
                                    </div>
                                    <div class="ml-auto">
                                        <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div x-show="open" class="mt-4 space-y-1">
                                <a href="#" class="sidebar-dropdown-link"><svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg><span>Bảo mật 2 lớp</span></a>
                                <a href="#" class="sidebar-dropdown-link"><svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><span>Cài đặt</span></a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="#" class="w-full text-left sidebar-dropdown-link text-red-600 hover:bg-red-50 hover:text-red-800"><svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg><span>Đăng xuất</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Header Example -->
            <div>
                 <h3 class="text-lg font-medium mb-4">Mobile Header</h3>
                 <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow-lg rounded-lg border border-gray-200">
                    <button class="px-4 border-r border-gray-200 text-gray-500">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <div class="flex-1 px-4 flex justify-end">
                        <div class="ml-4 flex items-center md:ml-6">
                            <button class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500">
                                <span class="sr-only">View notifications</span>
                                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                 </div>
            </div>
        </div>
    </section>

    <!-- Modal Examples -->
    <section class="compass-slide-up">
        <h2 class="text-2xl font-semibold mb-6">Modals</h2>
        <div x-data="{ 'isModalOpen': false }" @keydown.escape="isModalOpen = false">
            <!-- Trigger -->
            <button @click="isModalOpen = true" class="compass-btn-primary">
                Open Modal
            </button>

            <!-- Modal -->
            <div
                x-show="isModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75"
                style="display: none;"
            >
                <div
                    @click.away="isModalOpen = false"
                    x-show="isModalOpen"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b">
                        <h3 class="text-xl font-semibold text-gray-800">Modal Title</h3>
                        <!-- Close Button: "X" Icon -->
                        <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="py-4">
                        <p class="text-gray-600">
                            Đây là nội dung của modal. Bạn có thể đặt bất kỳ nội dung nào ở đây, từ văn bản, form cho đến các thành phần phức tạp khác. Việc đóng modal có thể được thực hiện bằng nút 'X', nút 'Đóng' bên dưới, hoặc click ra ngoài.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end pt-3 border-t space-x-2">
                        <!-- Close Button: "Secondary" Style -->
                        <button @click="isModalOpen = false" class="compass-btn-secondary">
                            Hủy
                        </button>
                        <!-- Primary Action Button -->
                        <button @click="isModalOpen = false" class="compass-btn-primary">
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
.compass-gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
}

.compass-gradient-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="20" cy="80" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

/* Social login button animations */
.social-btn {
    transition: all 0.3s ease;
    transform: translateY(0);
}

.social-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.social-btn-google:hover {
    box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
}

.social-btn-facebook:hover {
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}
</style>
@endpush

@push('scripts')
<script>
function customSelect(config) {
    return {
        open: false,
        options: config.options || [],
        selectedValue: config.initialValue || '',
        selectedText: '',

        init() {
            const initialOption = this.options.find(opt => opt.value === this.selectedValue);
            this.selectedText = initialOption ? initialOption.text : (this.options.length > 0 ? this.options[0].text : 'Select an option');
            if (!this.selectedValue && this.options.length > 0) {
                this.selectedValue = this.options[0].value;
            }
        },

        selectOption(option) {
            this.selectedValue = option.value;
            this.selectedText = option.text;
            this.open = false;
        }
    }
}
</script>
@endpush
@endsection
