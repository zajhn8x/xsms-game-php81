<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Hệ thống chọn cầu lô tối ưu</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans">
<div id="app">
    <nav class="bg-white shadow-lg border-b border-gray-200" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center space-x-3">
                        <div class="bg-primary-600 p-2 rounded-lg">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'XSMB Game') }}</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">

                    <!-- Navigation Links -->
                    <a href="/lottery" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Kết quả xổ số
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Dashboard
                        </a>

                        <!-- Ví & Giao Dịch Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                Ví & Giao Dịch
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                <a href="{{ route('wallet.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Quản Lý Ví</a>
                                <a href="{{ route('wallet.history') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Lịch Sử Giao Dịch</a>
                            </div>
                        </div>

                        <!-- Chiến Dịch Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                Chiến Dịch
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                <a href="{{ route('campaigns.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Quản Lý Chiến Dịch</a>
                                <a href="{{ route('campaigns.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tạo Chiến Dịch</a>
                                <a href="{{ route('historical-testing.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Test Lịch Sử</a>
                                <a href="{{ route('risk-management.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Risk Management</a>
                            </div>
                        </div>

                        <!-- Cộng đồng Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                Cộng đồng
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                <a href="{{ route('social.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Social Feed</a>
                                <a href="{{ route('social.leaderboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Bảng xếp hạng</a>
                                <div class="border-t border-gray-100"></div>
                                <a href="{{ route('social.profile', auth()->user()) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile của tôi</a>
                                <a href="{{ route('social.followers', auth()->user()) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Followers</a>
                                <a href="{{ route('social.following', auth()->user()) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đang theo dõi</a>
                            </div>
                        </div>

                        <!-- Phân Tích Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                Phân Tích
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                <a href="/caulo/heatmap" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Heatmap</a>
                                <a href="/caulo/heatmap-analytic" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Heatmap Analytics</a>
                                <a href="/statistics" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Thống Kê</a>
                            </div>
                        </div>
                    @endauth
                </div>

                <!-- Right Side - Auth Section -->
                <div class="hidden md:flex items-center space-x-4">
                    @guest
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                Đăng nhập
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary">
                                Đăng ký
                            </a>
                        @endif
                    @else
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <div class="border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 p-2 rounded-md">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen}" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-gray-200">
                    <a href="/lottery" class="text-gray-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">Kết quả xổ số</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>

                        <div class="space-y-1">
                            <div class="text-gray-500 text-sm font-medium px-3 py-2">Ví & Giao Dịch</div>
                            <a href="{{ route('wallet.index') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Quản Lý Ví</a>
                            <a href="{{ route('wallet.history') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Lịch Sử Giao Dịch</a>
                        </div>

                        <div class="space-y-1">
                            <div class="text-gray-500 text-sm font-medium px-3 py-2">Chiến Dịch</div>
                            <a href="{{ route('campaigns.index') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Quản Lý Chiến Dịch</a>
                            <a href="{{ route('campaigns.create') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Tạo Chiến Dịch</a>
                            <a href="{{ route('historical-testing.index') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Test Lịch Sử</a>
                            <a href="{{ route('risk-management.index') }}" class="text-gray-700 hover:text-primary-600 block px-6 py-2 rounded-md text-sm">Risk Management</a>
                        </div>

                        @guest
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">Đăng nhập</a>
                            <a href="{{ route('register') }}" class="text-gray-700 hover:text-primary-600 block px-3 py-2 rounded-md text-base font-medium">Đăng ký</a>
                        @else
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center px-3 py-2">
                                    <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                        <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        @endguest
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-sm text-gray-500">
                    © {{ date('Y') }} {{ config('app.name', 'XSMB Game') }}. All rights reserved.
                </div>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-sm text-gray-500 hover:text-primary-600">Privacy Policy</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-primary-600">Terms of Service</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-primary-600">Support</a>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div id="flash-message" class="fixed top-4 right-4 z-50 notification-success animate-slide-in">
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
            <button onclick="document.getElementById('flash-message').remove()" class="ml-4 text-success-500 hover:text-success-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif

@if(session('error'))
    <div id="flash-message" class="fixed top-4 right-4 z-50 notification-error animate-slide-in">
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            {{ session('error') }}
            <button onclick="document.getElementById('flash-message').remove()" class="ml-4 text-error-500 hover:text-error-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif

@if(session('warning'))
    <div id="flash-message" class="fixed top-4 right-4 z-50 notification-warning animate-slide-in">
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            {{ session('warning') }}
            <button onclick="document.getElementById('flash-message').remove()" class="ml-4 text-warning-500 hover:text-warning-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif

<script>
// Auto-hide flash messages after 5 seconds
setTimeout(() => {
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        flashMessage.style.transform = 'translateX(100%)';
        setTimeout(() => flashMessage.remove(), 300);
    }
}, 5000);
</script>

@stack('scripts')
</body>
</html>
