@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Dashboard Header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-sm text-gray-600">Tổng quan tài khoản và hoạt động của bạn</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-success-100 text-success-800">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Tài khoản hoạt động
            </span>
        </div>
    </div>
</div>

{{-- Wallet Summary Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Ví Thật Card --}}
    <x-ui.card
        title="Ví Thật"
        subtitle="Có thể rút được"
        :hover="true"
        class="bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200"
    >
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4">
            <div class="flex items-baseline">
                <div class="text-3xl font-bold text-primary-900">
                    {{ number_format($dashboardData['wallet_summary']['real_balance']) }}
                </div>
                <div class="ml-2 text-sm font-medium text-primary-600">VND</div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button size="sm" type="primary" href="{{ route('wallet.index') }}">
                Quản lý
            </x-ui.button>
        </x-slot:footer>
    </x-ui.card>

    {{-- Ví Ảo Card --}}
    <x-ui.card
        title="Ví Ảo"
        subtitle="Dùng để testing"
        :hover="true"
        class="bg-gradient-to-br from-info-50 to-info-100 border-info-200"
    >
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4">
            <div class="flex items-baseline">
                <div class="text-3xl font-bold text-info-900">
                    {{ number_format($dashboardData['wallet_summary']['virtual_balance']) }}
                </div>
                <div class="ml-2 text-sm font-medium text-info-600">VND</div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button size="sm" type="info" variant="outline" href="{{ route('historical-testing.index') }}">
                Test lịch sử
            </x-ui.button>
        </x-slot:footer>
    </x-ui.card>

    {{-- Ví Bonus Card --}}
    <x-ui.card
        title="Ví Bonus"
        subtitle="Từ khuyến mãi"
        :hover="true"
        class="bg-gradient-to-br from-warning-50 to-warning-100 border-warning-200"
    >
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4">
            <div class="flex items-baseline">
                <div class="text-3xl font-bold text-warning-900">
                    {{ number_format($dashboardData['wallet_summary']['bonus_balance']) }}
                </div>
                <div class="ml-2 text-sm font-medium text-warning-600">VND</div>
            </div>
        </div>

        <x-slot:footer>
            <div class="text-xs text-warning-600">
                Bonus không thể rút
            </div>
        </x-slot:footer>
    </x-ui.card>

    {{-- Tổng Cộng Card --}}
    <x-ui.card
        title="Tổng Cộng"
        subtitle="Tất cả ví"
        :hover="true"
        class="bg-gradient-to-br from-success-50 to-success-100 border-success-200"
    >
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4">
            <div class="flex items-baseline">
                <div class="text-3xl font-bold text-success-900">
                    {{ number_format($dashboardData['wallet_summary']['total_balance']) }}
                </div>
                <div class="ml-2 text-sm font-medium text-success-600">VND</div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button size="sm" type="success" href="{{ route('campaigns.create') }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tạo chiến dịch
            </x-ui.button>
        </x-slot:footer>
    </x-ui.card>
</div>

{{-- Campaign Overview Metrics --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Thống Kê Chiến Dịch</h2>
        <x-ui.button type="primary" size="sm" href="{{ route('campaigns.index') }}">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Xem tất cả
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Tổng Chiến Dịch --}}
        <x-ui.card
            title="Tổng Chiến Dịch"
            subtitle="Đã tạo"
            :hover="true"
            class="border-gray-200"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4">
                <div class="text-4xl font-bold text-gray-900">
                    {{ $dashboardData['campaign_overview']['total_campaigns'] }}
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    Chiến dịch tổng cộng
                </div>
            </div>
        </x-ui.card>

        {{-- Đang Hoạt Động --}}
        <x-ui.card
            title="Đang Hoạt Động"
            subtitle="Đang chạy"
            :hover="true"
            class="bg-gradient-to-br from-success-50 to-success-100 border-success-200"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-7a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4">
                <div class="text-4xl font-bold text-success-900">
                    {{ $dashboardData['campaign_overview']['active_campaigns'] }}
                </div>
                <div class="mt-2 flex items-center">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-success-500 rounded-full mr-1 animate-pulse"></div>
                        <span class="text-sm text-success-700 font-medium">Đang chạy</span>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button size="sm" type="success" variant="outline" href="{{ route('campaigns.index', ['status' => 'active']) }}">
                    Xem chi tiết
                </x-ui.button>
            </x-slot:footer>
        </x-ui.card>

        {{-- Đã Hoàn Thành --}}
        <x-ui.card
            title="Đã Hoàn Thành"
            subtitle="Đã kết thúc"
            :hover="true"
            class="bg-gradient-to-br from-info-50 to-info-100 border-info-200"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4">
                <div class="text-4xl font-bold text-info-900">
                    {{ $dashboardData['campaign_overview']['completed_campaigns'] }}
                </div>
                <div class="mt-2 text-sm text-info-700">
                    Hoàn thành thành công
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button size="sm" type="info" variant="outline" href="{{ route('campaigns.index', ['status' => 'completed']) }}">
                    Xem kết quả
                </x-ui.button>
            </x-slot:footer>
        </x-ui.card>

        {{-- Chiến Dịch Công Khai --}}
        <x-ui.card
            title="Chiến Dịch Công Khai"
            subtitle="Có thể chia sẻ"
            :hover="true"
            class="bg-gradient-to-br from-warning-50 to-warning-100 border-warning-200"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4">
                <div class="text-4xl font-bold text-warning-900">
                    {{ $dashboardData['campaign_overview']['public_campaigns'] }}
                </div>
                <div class="mt-2 text-sm text-warning-700">
                    Có thể chia sẻ với cộng đồng
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button size="sm" type="warning" variant="outline" href="{{ route('social.index') }}">
                    Xem cộng đồng
                </x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </div>
</div>

{{-- Performance Metrics Section --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Hiệu Suất Đầu Tư</h2>
        <x-ui.button type="info" size="sm" href="{{ route('statistics.index') }}">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Thống kê chi tiết
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Performance Metrics Cards --}}
        <div class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Tổng Đầu Tư --}}
                <x-ui.card
                    title="Tổng Đầu Tư"
                    subtitle="Số tiền đã đầu tư"
                    :hover="true"
                    class="bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200"
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </x-slot:icon>

                    <div class="mt-4">
                        <div class="flex items-baseline">
                            <div class="text-3xl font-bold text-primary-900">
                                {{ number_format($dashboardData['performance_metrics']['total_invested']) }}
                            </div>
                            <div class="ml-2 text-sm font-medium text-primary-600">VND</div>
                        </div>
                        <div class="mt-2 text-sm text-primary-700">
                            Vốn ban đầu
                        </div>
                    </div>
                </x-ui.card>

                {{-- Giá Trị Hiện Tại --}}
                <x-ui.card
                    title="Giá Trị Hiện Tại"
                    subtitle="Tổng giá trị portfolio"
                    :hover="true"
                    class="bg-gradient-to-br from-info-50 to-info-100 border-info-200"
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </x-slot:icon>

                    <div class="mt-4">
                        <div class="flex items-baseline">
                            <div class="text-3xl font-bold text-info-900">
                                {{ number_format($dashboardData['performance_metrics']['current_value']) }}
                            </div>
                            <div class="ml-2 text-sm font-medium text-info-600">VND</div>
                        </div>
                        <div class="mt-2 text-sm text-info-700">
                            Giá trị thời gian thực
                        </div>
                    </div>
                </x-ui.card>

                {{-- Lợi Nhuận --}}
                <x-ui.card
                    title="Lợi Nhuận"
                    subtitle="Tổng lãi/lỗ"
                    :hover="true"
                    class="bg-gradient-to-br from-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-50 to-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-100 border-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-200"
                >
                    <x-slot:icon>
                        @if($dashboardData['performance_metrics']['total_profit'] >= 0)
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                            </svg>
                        @endif
                    </x-slot:icon>

                    <div class="mt-4">
                        <div class="flex items-baseline">
                            <div class="text-3xl font-bold text-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-900">
                                {{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? '+' : '' }}{{ number_format($dashboardData['performance_metrics']['total_profit']) }}
                            </div>
                            <div class="ml-2 text-sm font-medium text-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-600">VND</div>
                        </div>
                        <div class="mt-2 flex items-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-100 text-{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'success' : 'error' }}-800">
                                {{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? '+' : '' }}{{ $dashboardData['performance_metrics']['profit_percentage'] }}%
                            </span>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Tỷ Lệ Thắng --}}
                <x-ui.card
                    title="Tỷ Lệ Thắng"
                    subtitle="Phần trăm chiến thắng"
                    :hover="true"
                    class="bg-gradient-to-br from-warning-50 to-warning-100 border-warning-200"
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </x-slot:icon>

                    <div class="mt-4">
                        <div class="flex items-baseline">
                            <div class="text-3xl font-bold text-warning-900">
                                {{ $dashboardData['performance_metrics']['win_rate'] }}
                            </div>
                            <div class="ml-2 text-sm font-medium text-warning-600">%</div>
                        </div>
                        <div class="mt-2">
                            <x-ui.progress
                                :percentage="$dashboardData['performance_metrics']['win_rate']"
                                color="warning"
                                size="sm"
                            />
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Profit Chart --}}
        <div class="lg:col-span-1">
            <x-ui.card
                title="Biểu Đồ Lợi Nhuận"
                subtitle="Theo thời gian"
                class="h-full"
            >
                <x-slot:icon>
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </x-slot:icon>

                <div class="mt-4 h-64">
                    <canvas id="profitChart" class="w-full h-full"></canvas>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

{{-- Recent Activities Section --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Hoạt Động Gần Đây</h2>
        <div class="flex space-x-3">
            <x-ui.button type="primary" size="sm" variant="outline" href="{{ route('wallet.index') }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Lịch sử giao dịch
            </x-ui.button>
            <x-ui.button type="info" size="sm" variant="outline" href="{{ route('campaigns.index') }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Lịch sử cược
            </x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Transactions --}}
        <x-ui.card
            title="Giao Dịch Gần Đây"
            subtitle="10 giao dịch mới nhất"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4 space-y-4 max-h-80 overflow-y-auto">
                @if(count($dashboardData['recent_activities']['recent_transactions']) > 0)
                    @foreach($dashboardData['recent_activities']['recent_transactions'] as $transaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($transaction['type'] === 'deposit')
                                        <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                    @elseif($transaction['type'] === 'withdrawal')
                                        <div class="w-10 h-10 bg-error-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 bg-info-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ ucfirst($transaction['type']) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $transaction['created_at']->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $transaction['status'] === 'completed'
                                        ? 'bg-success-100 text-success-800'
                                        : 'bg-warning-100 text-warning-800' }}">
                                    {{ number_format($transaction['amount']) }} VND
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $transaction['status'] === 'completed' ? 'Hoàn thành' : 'Đang xử lý' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <div class="mt-2 text-sm text-gray-500">Chưa có giao dịch nào</div>
                    </div>
                @endif
            </div>

            @if(count($dashboardData['recent_activities']['recent_transactions']) > 0)
                <x-slot:footer>
                    <x-ui.button size="sm" type="primary" variant="outline" href="{{ route('wallet.index') }}" class="w-full">
                        Xem tất cả giao dịch
                    </x-ui.button>
                </x-slot:footer>
            @endif
        </x-ui.card>

        {{-- Recent Bets --}}
        <x-ui.card
            title="Cược Gần Đây"
            subtitle="10 cược mới nhất"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </x-slot:icon>

            <div class="mt-4 space-y-4 max-h-80 overflow-y-auto">
                @if(count($dashboardData['recent_activities']['recent_bets']) > 0)
                    @foreach($dashboardData['recent_activities']['recent_bets'] as $bet)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($bet['is_win'])
                                        <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 bg-error-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 truncate max-w-48">
                                        {{ $bet['campaign_name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $bet['created_at']->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $bet['is_win']
                                        ? 'bg-success-100 text-success-800'
                                        : 'bg-error-100 text-error-800' }}">
                                    {{ $bet['is_win'] ? '+' : '' }}{{ number_format($bet['profit']) }} VND
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $bet['is_win'] ? 'Thắng' : 'Thua' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <div class="mt-2 text-sm text-gray-500">Chưa có cược nào</div>
                    </div>
                @endif
            </div>

            @if(count($dashboardData['recent_activities']['recent_bets']) > 0)
                <x-slot:footer>
                    <x-ui.button size="sm" type="info" variant="outline" href="{{ route('campaigns.index') }}" class="w-full">
                        Xem tất cả cược
                    </x-ui.button>
                </x-slot:footer>
            @endif
        </x-ui.card>
    </div>
</div>

{{-- Quick Actions Section --}}
<div class="mb-8">
    <x-ui.card
        title="Thao Tác Nhanh"
        subtitle="Các tính năng thường dùng"
    >
        <x-slot:icon>
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Quản Lý Ví --}}
                <x-ui.button
                    type="primary"
                    href="{{ route('wallet.index') }}"
                    class="h-20 flex-col justify-center"
                >
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="text-sm font-medium">Quản Lý Ví</span>
                </x-ui.button>

                {{-- Tạo Chiến Dịch --}}
                <x-ui.button
                    type="success"
                    href="{{ route('campaigns.create') }}"
                    class="h-20 flex-col justify-center"
                >
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span class="text-sm font-medium">Tạo Chiến Dịch</span>
                </x-ui.button>

                {{-- Test Lịch Sử --}}
                <x-ui.button
                    type="info"
                    href="{{ route('historical-testing.index') }}"
                    class="h-20 flex-col justify-center"
                >
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">Test Lịch Sử</span>
                </x-ui.button>

                {{-- Xem Chiến Dịch --}}
                <x-ui.button
                    type="warning"
                    href="{{ route('campaigns.index') }}"
                    class="h-20 flex-col justify-center"
                >
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="text-sm font-medium">Xem Chiến Dịch</span>
                </x-ui.button>
            </div>

            {{-- Additional Quick Actions Row --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                {{-- Thống Kê --}}
                <x-ui.button
                    variant="outline"
                    type="primary"
                    href="{{ route('statistics.index') }}"
                    class="h-16 flex-col justify-center"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-sm">Thống Kê</span>
                </x-ui.button>

                {{-- Cộng Đồng --}}
                <x-ui.button
                    variant="outline"
                    type="warning"
                    href="{{ route('social.index') }}"
                    class="h-16 flex-col justify-center"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-sm">Cộng Đồng</span>
                </x-ui.button>

                {{-- Heatmap --}}
                <x-ui.button
                    variant="outline"
                    type="info"
                    href="{{ route('heatmap.index') }}"
                    class="h-16 flex-col justify-center"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <span class="text-sm">Heatmap</span>
                </x-ui.button>

                {{-- Quản Lý Rủi Ro --}}
                <x-ui.button
                    variant="outline"
                    type="error"
                    href="{{ route('risk-management.index') }}"
                    class="h-16 flex-col justify-center"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span class="text-sm">Rủi Ro</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profit Chart with Tailwind styling
    const profitCtx = document.getElementById('profitChart');
    if (profitCtx) {
        const profitData = @json($dashboardData['charts_data']['profit_loss_chart']);

        new Chart(profitCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: profitData.map(item => item.date),
                datasets: [{
                    label: 'Lợi Nhuận',
                    data: profitData.map(item => item.profit),
                    borderColor: '#0891b2', // cyan-600
                    backgroundColor: 'rgba(8, 145, 178, 0.1)', // cyan-600 with opacity
                    pointBackgroundColor: '#0891b2',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#374151', // gray-700
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#6b7280', // gray-500
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Lợi nhuận: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VND';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f3f4f6', // gray-100
                            borderColor: '#e5e7eb' // gray-200
                        },
                        ticks: {
                            color: '#6b7280', // gray-500
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6', // gray-100
                            borderColor: '#e5e7eb' // gray-200
                        },
                        ticks: {
                            color: '#6b7280', // gray-500
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', {
                                    notation: 'compact',
                                    compactDisplay: 'short'
                                }).format(value) + ' VND';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
