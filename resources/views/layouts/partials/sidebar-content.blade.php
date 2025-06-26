@auth
<div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
    <div class="flex items-center flex-shrink-0 px-4">
        <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
            <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary-600 to-info-600 rounded-xl blur-lg opacity-75 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative bg-gray-800 p-3 rounded-xl shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-info-600 bg-clip-text text-transparent">{{ config('app.name', 'XSMB Game') }}</span>
                <p class="text-xs text-gray-500 -mt-1">Phân tích thông minh</p>
            </div>
        </a>
    </div>
    <nav class="mt-8 flex-1 px-2 space-y-1">
        <!-- User info banner -->
        <div class="mx-1 mb-6 p-4 bg-gradient-to-r from-primary-50 to-indigo-50 border border-primary-200 rounded-xl">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                        <span class="text-white text-sm font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-primary-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-primary-700">
                        {{ Auth::user()->subscription_type == 'trial' ? 'Dùng thử' : (Auth::user()->subscription_type == 'premium' ? 'Premium' : 'Thành viên') }}
                    </p>
                </div>
                @if(Auth::user()->subscription_type == 'trial')
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        Trial
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Main Navigation -->
        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span>Chính</span>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="sidebar-link group {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-primary-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <span class="flex-1">Dashboard</span>
            </div>
        </a>

        <a href="{{ route('home') }}" class="sidebar-link group {{ request()->routeIs('home') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-green-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="flex-1">Kết quả xổ số</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">LIVE</span>
            </div>
        </a>

        <!-- Analytics Section -->
        <div class="px-3 py-2 mt-6 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span>Phân tích</span>
            </div>
        </div>

        <a href="{{ route('caulo.find') }}" class="sidebar-link group {{ request()->routeIs('caulo.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-purple-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="flex-1">Thống kê cầu lô</span>
            </div>
        </a>

        <a href="{{ route('heatmap.index') }}" class="sidebar-link group {{ request()->routeIs('heatmap.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-orange-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <span class="flex-1">Phân tích heatmap</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">AI</span>
            </div>
        </a>

        <a href="{{ route('statistics.index') }}" class="sidebar-link group {{ request()->routeIs('statistics.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-blue-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="flex-1">Thống kê tổng hợp</span>
            </div>
        </a>

        <!-- Trading Section -->
        <div class="px-3 py-2 mt-6 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
                <span>Giao dịch</span>
            </div>
        </div>

        <a href="{{ route('campaigns.index') }}" class="sidebar-link group {{ request()->routeIs('campaigns.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-yellow-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="flex-1">Quản lý chiến dịch</span>
            </div>
        </a>

        <a href="{{ route('wallet.index') }}" class="sidebar-link group {{ request()->routeIs('wallet.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-green-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <span class="flex-1">Ví điện tử</span>
                @if(Auth::user()->wallet && Auth::user()->wallet->balance > 0)
                <span class="text-xs text-green-600 font-medium">{{ number_format(Auth::user()->wallet->balance) }}đ</span>
                @endif
            </div>
        </a>

        <!-- Social Section -->
        <div class="px-3 py-2 mt-6 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Cộng đồng</span>
            </div>
        </div>

        <a href="{{ route('social.index') }}" class="sidebar-link group {{ request()->routeIs('social.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-pink-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="flex-1">Cộng đồng</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">NEW</span>
            </div>
        </a>
    </nav>
</div>
<div class="flex-shrink-0 flex border-t border-gray-200 p-4">
    <div class="w-full" x-data="{ open: false }">
        <div @click="open = !open" class="flex-shrink-0 w-full group block cursor-pointer">
            <div class="flex items-center">
                <div>
                    <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-info-500 rounded-full flex items-center justify-center shadow-md">
                        <span class="text-white text-sm font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700 transition-colors">
                       Xem hồ sơ & Cài đặt
                    </p>
                </div>
                <div class="ml-auto">
                     <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="mt-4 space-y-1"
             style="display: none;">
             <a href="{{ route('two-factor.index') }}" class="sidebar-dropdown-link">
                <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Bảo mật 2 lớp
             </a>
             <a href="#" class="sidebar-dropdown-link">
                <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Cài đặt
             </a>
             <div class="border-t border-gray-200 my-1"></div>
             <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left sidebar-dropdown-link text-red-600 hover:bg-red-50 hover:text-red-800">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Đăng xuất
                </button>
             </form>
        </div>
    </div>
</div>
@endauth

@guest
<div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
    <div class="flex items-center flex-shrink-0 px-4">
        <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
            <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary-600 to-info-600 rounded-xl blur-lg opacity-75 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative bg-gray-800 p-3 rounded-xl shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-info-600 bg-clip-text text-transparent">{{ config('app.name', 'XSMB Game') }}</span>
                <p class="text-xs text-gray-500 -mt-1">Phân tích thông minh</p>
            </div>
        </a>
    </div>

    <nav class="mt-8 flex-1 px-2 space-y-1">
        <!-- Banner thông báo -->
        <div class="mx-3 mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-blue-900">Khám phá miễn phí</p>
                    <p class="text-xs text-blue-700">Trải nghiệm các tính năng cơ bản</p>
                </div>
            </div>
        </div>

        <!-- Khám phá -->
        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Khám phá</span>
            </div>
        </div>

        <a href="{{ url('/') }}" class="sidebar-link group {{ request()->is('/') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-primary-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <span class="flex-1">Trang chủ</span>
            </div>
        </a>

        <a href="{{ route('home') }}" class="sidebar-link group {{ request()->routeIs('home') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-green-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="flex-1">Kết quả xổ số</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">LIVE</span>
            </div>
        </a>

        <a href="{{ route('caulo.find') }}" class="sidebar-link group {{ request()->routeIs('caulo.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-purple-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="flex-1">Thống kê cầu lô</span>
            </div>
        </a>

        <a href="{{ route('heatmap.index') }}" class="sidebar-link group {{ request()->routeIs('heatmap.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-orange-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <span class="flex-1">Phân tích heatmap</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">HOT</span>
            </div>
        </a>

        <a href="{{ route('statistics.index') }}" class="sidebar-link group {{ request()->routeIs('statistics.*') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-blue-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="flex-1">Thống kê tổng hợp</span>
            </div>
        </a>

        <!-- Tài khoản -->
        <div class="px-3 py-2 mt-6 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 mb-2">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Tài khoản</span>
            </div>
        </div>

        <a href="{{ route('login') }}" class="sidebar-link group {{ request()->routeIs('login') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-green-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <span class="flex-1">Đăng nhập</span>
            </div>
        </a>

        <a href="{{ route('register') }}" class="sidebar-link group {{ request()->routeIs('register') ? 'sidebar-link-active' : '' }}">
            <div class="flex items-center">
                <div class="mr-3 p-1 rounded-lg bg-gray-100 group-hover:bg-blue-100 transition-colors">
                    <svg class="h-5 w-5 text-gray-600 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <span class="flex-1">Đăng ký</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">FREE</span>
            </div>
        </a>
    </nav>
</div>

<!-- Call to Action Footer -->
<div class="flex-shrink-0 border-t border-gray-200 p-4">
    <div class="w-full">
        <!-- Social Login Preview -->
        <div class="mb-4 p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
            <div class="text-center">
                <div class="text-xs font-medium text-gray-700 mb-2">Đăng nhập nhanh với</div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('auth.social.redirect', 'google') }}" class="flex items-center justify-center py-2 px-3 border border-gray-300 rounded-md bg-white hover:bg-gray-50 transition-colors text-xs font-medium text-gray-700">
                        <svg class="w-3 h-3 mr-1.5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </a>
                    <a href="{{ route('auth.social.redirect', 'facebook') }}" class="flex items-center justify-center py-2 px-3 border border-blue-300 rounded-md bg-blue-600 hover:bg-blue-700 transition-colors text-xs font-medium text-white">
                        <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>
                </div>
            </div>
        </div>

        <!-- Main CTA Button -->
        <a href="{{ route('login') }}" class="w-full inline-flex justify-center items-center px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 border border-transparent rounded-xl font-semibold text-sm text-white uppercase tracking-wider hover:from-primary-700 hover:to-primary-800 active:from-primary-800 active:to-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Đăng nhập ngay
        </a>

        <div class="mt-3 text-center">
            <a href="{{ route('register') }}" class="text-sm text-gray-600 hover:text-primary-600 transition-colors font-medium">
                Chưa có tài khoản? <span class="text-primary-600">Đăng ký miễn phí</span>
            </a>
        </div>

        <!-- Features Preview -->
        <div class="mt-4 text-center">
            <div class="text-xs text-gray-500 mb-2">Tính năng khi đăng nhập:</div>
            <div class="flex items-center justify-center space-x-4 text-xs text-gray-600">
                <div class="flex items-center space-x-1">
                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>AI Dự đoán</span>
                </div>
                <div class="flex items-center space-x-1">
                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>Ví điện tử</span>
                </div>
                <div class="flex items-center space-x-1">
                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>Cộng đồng</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endguest
