<!-- Layout Check -->
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'XSMB Game')) - Hệ thống phân tích xổ số thông minh</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">
<div id="app" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="flex h-screen bg-gray-100">
    <!-- Off-canvas menu for mobile -->
    <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" x-ref="dialog" aria-modal="true">
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false" aria-hidden="true">
        </div>

        <div x-show="sidebarOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6 text-white" x-description="Heroicon name: outline/x" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            @include('layouts.partials.sidebar-content')
        </div>
        <div class="flex-shrink-0 w-14" aria-hidden="true"></div>
    </div>

    <!-- Static sidebar for desktop -->
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64">
            <div class="flex-1 flex flex-col min-h-0 border-r border-gray-200 bg-white">
                @include('layouts.partials.sidebar-content')
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex flex-col w-0 flex-1 overflow-hidden">
        <!-- Mobile Header -->
        <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow-md md:hidden">
            <button @click.stop="sidebarOpen = true" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </button>
            <div class="flex-1 px-4 flex justify-end">
                <div class="ml-4 flex items-center md:ml-6">
                    <button class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">View notifications</span>
                        <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <main class="flex-1 relative overflow-y-auto focus:outline-none">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    @if (session('success'))
                        <div class="compass-alert-success mb-4">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                         <div class="compass-alert-danger mb-4">{{ session('error') }}</div>
                    @endif
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Footer - Compass Theme -->
<footer class="bg-gradient-to-r from-gray-900 to-gray-800 text-white mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- About -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Về chúng tôi
                </h3>
                <p class="text-gray-400 text-sm">
                    {{ config('app.name', 'XSMB Game') }} - Hệ thống phân tích xổ số thông minh,
                    cung cấp các công cụ phân tích chuyên sâu và chiến lược đầu tư hiệu quả.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Liên kết nhanh
                </h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('campaigns.index') }}" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Chiến dịch</a></li>
                    <li><a href="/statistics" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Thống kê</a></li>
                    <li><a href="/caulo/heatmap" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Phân tích</a></li>
                    <li><a href="{{ route('social.leaderboard') }}" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Bảng xếp hạng</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Hỗ trợ
                </h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Hướng dẫn sử dụng</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Liên hệ hỗ trợ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Điều khoản sử dụng</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Kết nối
                </h3>
                <div class="space-y-2">
                    <p class="text-gray-400 text-sm">Email: support@xsmbgame.com</p>
                    <p class="text-gray-400 text-sm">Hotline: 1900 xxxx</p>
                    <div class="flex space-x-3 mt-4">
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
            <div class="text-sm text-gray-400">
                © {{ date('Y') }} {{ config('app.name', 'XSMB Game') }}. All rights reserved.
            </div>
            <div class="flex space-x-6 mt-4 md:mt-0">
                <a href="#" class="text-sm text-gray-400 hover:text-primary-400 transition-colors">Chính sách bảo mật</a>
                <a href="#" class="text-sm text-gray-400 hover:text-primary-400 transition-colors">Điều khoản dịch vụ</a>
            </div>
        </div>
    </div>
</footer>

<style>
/* Navigation Styles */
.nav-link {
    @apply flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:text-primary-600 hover:bg-primary-50 transition-all duration-200;
}

.nav-link-active {
    @apply text-primary-600 bg-primary-50;
}

.sidebar-link {
    @apply flex items-center px-3 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-all duration-200;
}

.sidebar-link-active {
    @apply bg-primary-100 text-primary-700 font-semibold shadow-inner;
}

.sidebar-dropdown-link {
    @apply flex items-center w-full text-left px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-100 hover:text-gray-800 transition-colors;
}

.dropdown-menu {
    @apply absolute z-20 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2
           transform opacity-0 invisible scale-95 transition-all duration-200;
}

.dropdown-menu.show,
[x-show="open"] {
    @apply opacity-100 visible scale-100;
}

.dropdown-link {
    @apply flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors;
}

.mobile-nav-link {
    @apply flex items-center px-3 py-3 text-base font-medium text-gray-700 rounded-lg hover:text-primary-600 hover:bg-gray-50;
}

/* Animations */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100;
}

::-webkit-scrollbar-thumb {
    @apply bg-gray-400 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-500;
}
</style>

<script>
// Auto-hide flash messages after 5 seconds
setTimeout(() => {
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        flashMessage.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => flashMessage.remove(), 300);
    }
}, 5000);
</script>

@stack('scripts')
</body>
</html>
