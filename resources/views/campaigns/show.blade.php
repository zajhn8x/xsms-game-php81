@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-8">
    <!-- Header với breadcrumb và actions - Mobile Optimized -->
    <div class="compass-fade-in">
        <div class="space-y-4 mb-6 md:mb-8">
            <!-- Mobile Breadcrumb -->
            <nav class="text-sm compass-text-muted">
                <a href="{{ route('campaigns.index') }}" class="hover:text-primary-600 transition-colors touch-manipulation">Chiến dịch</a>
                <span class="mx-2">></span>
                <span class="truncate">Chi tiết #{{ $campaign->id }}</span>
            </nav>

            <!-- Title Section -->
            <div>
                <h1 class="text-2xl md:text-3xl font-bold compass-text-gradient">Chi tiết Chiến dịch #{{ $campaign->id }}</h1>
                <p class="text-gray-600 mt-1 text-sm md:text-base">{{ $campaign->notes ?: 'Theo dõi và quản lý hoạt động chiến dịch' }}</p>
            </div>

            <!-- Mobile Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 w-full">
                <a href="{{ route('campaigns.index') }}" class="compass-btn-secondary flex-1 sm:flex-none justify-center touch-manipulation">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Quay lại
                </a>
                @if($campaign->status == 'running' or $campaign->status == 'active')
                <button type="button" class="compass-btn-primary flex-1 sm:flex-none justify-center touch-manipulation" data-bs-toggle="modal" data-bs-target="#addBetModal">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Thêm cược
                </button>
                @endif
            </div>
        </div>
    </div>

        <!-- Status và Quick Actions - Mobile Optimized -->
    <div class="compass-slide-up">
        <div class="compass-card bg-gradient-to-r from-primary-50 to-blue-50 border-primary-200">
            <div class="flex flex-col space-y-4">
                <!-- Status và thông tin -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                        <div class="status-indicator">
                            @switch($campaign->status)
                                @case('active')
                                    <span class="compass-badge bg-blue-100 text-blue-800 border-blue-200">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                        Sẵn sàng
                                    </span>
                                    @break
                                @case('running')
                                    <span class="compass-badge bg-green-100 text-green-800 border-green-200">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                        Đang chạy
                                    </span>
                                    @break
                                @case('paused')
                                    <span class="compass-badge bg-yellow-100 text-yellow-800 border-yellow-200">
                                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                        Tạm dừng
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="compass-badge bg-gray-100 text-gray-800 border-gray-200">
                                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                        Đã kết thúc
                                    </span>
                                    @break
                                @default
                                    <span class="compass-badge bg-gray-100 text-gray-800 border-gray-200">
                                        {{ $campaign->status }}
                                    </span>
                            @endswitch
                        </div>
                        <div class="text-sm compass-text-muted">
                            <div>Bắt đầu: <span class="font-medium">{{ $campaign->start_date->format('d/m/Y') }}</span></div>
                            <div>Thời gian chạy: <span class="font-medium">{{ $campaign->days }} ngày</span></div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons cho mobile -->
                <div class="flex flex-col sm:flex-row gap-2">
                    @if($campaign->status === 'active' || $campaign->status === 'waiting')
                    <button class="compass-btn-success compass-btn-sm flex-1 touch-manipulation justify-center" onclick="runCampaign({{ $campaign->id }})">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 10a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Chạy chiến dịch
                    </button>
                    <button class="compass-btn-warning compass-btn-sm flex-1 touch-manipulation justify-center" onclick="pauseCampaign({{ $campaign->id }})">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Tạm dừng
                    </button>
                    @endif
                    <button class="compass-btn-danger compass-btn-sm flex-1 touch-manipulation justify-center" onclick="deleteCampaign({{ $campaign->id }})">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Xóa chiến dịch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Grid - Mobile Optimized -->
    <div class="compass-slide-up" style="animation-delay: 0.1s">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
            <!-- Total Balance Card - Mobile -->
            <div class="compass-card compass-card-hover group col-span-2 md:col-span-1">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
                    <div class="flex-1">
                        <p class="text-xs md:text-sm font-medium compass-text-muted group-hover:text-primary-600 transition-colors">Số dư hiện tại</p>
                        <p class="text-lg md:text-2xl font-bold text-primary-600">{{ number_format($campaign->current_balance) }}đ</p>
                        <p class="text-xs compass-text-muted mt-1">
                            Từ {{ number_format($campaign->initial_balance) }}đ ban đầu
                        </p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors md:ml-2">
                        <svg class="w-5 h-5 md:w-6 md:h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Bets Card -->
            <div class="compass-card compass-card-hover group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium compass-text-muted group-hover:text-blue-600 transition-colors">Tổng cược</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($campaign->total_bet_amount) }}đ</p>
                        <p class="text-xs compass-text-muted mt-1">
                            {{ $campaign->total_bets }} lần đặt cược
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="compass-card compass-card-hover group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium compass-text-muted group-hover:text-green-600 transition-colors">Lợi nhuận</p>
                        <p class="text-2xl font-bold {{ ($campaign->current_balance - $campaign->initial_balance) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($campaign->current_balance - $campaign->initial_balance) }}đ
                        </p>
                        <p class="text-xs compass-text-muted mt-1">
                            Từ {{ $campaign->winning_bets ?? 0 }} cược thắng
                        </p>
                    </div>
                    <div class="w-12 h-12 {{ ($campaign->current_balance - $campaign->initial_balance) >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg flex items-center justify-center group-hover:{{ ($campaign->current_balance - $campaign->initial_balance) >= 0 ? 'bg-green-200' : 'bg-red-200' }} transition-colors">
                        <svg class="w-6 h-6 {{ ($campaign->current_balance - $campaign->initial_balance) >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if(($campaign->current_balance - $campaign->initial_balance) >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                            @endif
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Win Rate Card -->
            <div class="compass-card compass-card-hover group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium compass-text-muted group-hover:text-purple-600 transition-colors">Tỷ lệ thắng</p>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($campaign->win_rate, 1) }}%</p>
                        <p class="text-xs compass-text-muted mt-1">
                            {{ $campaign->winning_bets ?? 0 }}/{{ $campaign->total_bets }} cược
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid - Mobile Optimized -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">
        <!-- Campaign Info Panel -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Campaign Details -->
            <div class="compass-card compass-slide-up" style="animation-delay: 0.2s">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Thông tin chiến dịch
                    </h3>
                </div>
                <div class="compass-card-body space-y-4">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium compass-text-muted">Loại chiến dịch</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">
                                @switch($campaign->bet_type)
                                    @case('manual')
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Thủ công
                                        </span>
                                        @break
                                    @case('auto_heatmap')
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                            </svg>
                                            AI Heatmap
                                        </span>
                                        @break
                                    @case('auto_streak')
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                            Streak Analysis
                                        </span>
                                        @break
                                    @default
                                        {{ $campaign->bet_type }}
                                @endswitch
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium compass-text-muted">Thời gian</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">
                                {{ $campaign->start_date->format('d/m/Y') }}
                                @if($campaign->end_date)
                                    - {{ $campaign->end_date->format('d/m/Y') }}
                                @else
                                    - Không giới hạn
                                @endif
                            </dd>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium compass-text-muted">Tiến độ</dt>
                            <dd class="mt-1">
                                @php
                                    $startDate = $campaign->start_date;
                                    $currentDate = now();
                                    $totalDays = $campaign->days;
                                    $daysPassed = $startDate->diffInDays($currentDate);
                                    $progress = min(100, max(0, ($daysPassed / $totalDays) * 100));
                                @endphp
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="font-semibold text-gray-900">{{ number_format($progress, 1) }}%</span>
                                    <span class="compass-text-muted">{{ $daysPassed }}/{{ $totalDays }} ngày</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                                </div>
                            </dd>
                        </div>

                        @if($campaign->notes)
                        <div class="bg-gray-50 rounded-lg p-3">
                            <dt class="text-sm font-medium compass-text-muted">Ghi chú</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $campaign->notes }}</dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="compass-card compass-slide-up" style="animation-delay: 0.3s">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Thống kê hiệu suất
                    </h3>
                </div>
                <div class="compass-card-body space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-primary-50 rounded-lg">
                            <div class="text-lg font-bold text-primary-600">{{ number_format($campaign->total_bet_amount) }}đ</div>
                            <div class="text-xs compass-text-muted">Tổng cược</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <div class="text-lg font-bold text-green-600">{{ number_format($campaign->total_win_amount) }}đ</div>
                            <div class="text-xs compass-text-muted">Tổng thắng</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <div class="text-lg font-bold text-blue-600">{{ $campaign->total_bets }}</div>
                            <div class="text-xs compass-text-muted">Tổng lần cược</div>
                        </div>
                        <div class="text-center p-3 bg-purple-50 rounded-lg">
                            <div class="text-lg font-bold text-purple-600">{{ number_format($campaign->win_rate, 1) }}%</div>
                            <div class="text-xs compass-text-muted">Tỷ lệ thắng</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Betting History -->
        <div class="lg:col-span-2">
            <div class="compass-card compass-slide-up" style="animation-delay: 0.4s">
                <div class="compass-card-header">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            Lịch sử cược
                            <span class="ml-2 compass-badge bg-blue-100 text-blue-800">{{ $bets->count() }} lượt</span>
                        </h3>
                        @if($campaign->status == 'running' or $campaign->status == 'active')
                        <button type="button" class="compass-btn-primary compass-btn-sm" data-bs-toggle="modal" data-bs-target="#addBetModal">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Thêm cược
                        </button>
                        @endif
                    </div>
                </div>
                <div class="compass-card-body">
                    @if($bets->isEmpty())
                    <div class="text-center py-8 md:py-12">
                        <svg class="w-12 h-12 md:w-16 md:h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                        </svg>
                        <h3 class="text-base md:text-lg font-medium text-gray-900 mb-2">Chưa có lượt cược nào</h3>
                        <p class="text-sm md:text-base text-gray-500 mb-4 px-4">Bắt đầu thêm cược đầu tiên để theo dõi hiệu suất chiến dịch.</p>
                        @if($campaign->status == 'running' or $campaign->status == 'active')
                        <button type="button" class="compass-btn-primary w-full sm:w-auto touch-manipulation" data-bs-toggle="modal" data-bs-target="#addBetModal">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Thêm cược đầu tiên
                        </button>
                        @endif
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lô</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền cược</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kết quả</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lợi nhuận</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($bets as $bet)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $bet->bet_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(json_decode($bet->bet_numbers) as $number)
                                            <span class="compass-badge bg-blue-100 text-blue-800">{{ $number }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($bet->bet_amount) }}đ
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($bet->result > 0)
                                        <span class="compass-badge bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Trúng {{ $bet->result }} lần
                                        </span>
                                        @else
                                        <span class="compass-badge bg-red-100 text-red-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                            Thua
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <span class="{{ $bet->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $bet->profit >= 0 ? '+' : '' }}{{ number_format($bet->profit) }}đ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="compass-btn-secondary compass-btn-sm" data-bs-toggle="modal" data-bs-target="#betDetailModal{{ $bet->id }}">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Chi tiết
                                        </button>

                                        <!-- Modal phân tích bet -->
                                        <div class="modal fade" id="betDetailModal{{ $bet->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Phân tích chi tiết cược ngày {{ $bet->bet_date->format('d/m/Y') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="space-y-4">
                                                            <div>
                                                                <h6 class="font-semibold text-gray-900 mb-2">Số lô đã cược:</h6>
                                                                <div class="flex flex-wrap gap-2">
                                                                    @foreach(json_decode($bet->bet_numbers) as $number)
                                                                    <span class="compass-badge bg-blue-100 text-blue-800">{{ $number }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>

                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div class="bg-gray-50 p-3 rounded-lg">
                                                                    <div class="text-sm font-medium text-gray-600">Số tiền cược</div>
                                                                    <div class="text-lg font-bold text-gray-900">{{ number_format($bet->bet_amount) }}đ</div>
                                                                </div>
                                                                <div class="bg-gray-50 p-3 rounded-lg">
                                                                    <div class="text-sm font-medium text-gray-600">Kết quả</div>
                                                                    <div class="text-lg font-bold {{ $bet->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                        {{ $bet->profit >= 0 ? '+' : '' }}{{ number_format($bet->profit) }}đ
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bet Modal - Bootstrap 5 -->
<div class="modal fade" id="addBetModal" tabindex="-1" aria-labelledby="addBetModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-gray-900" id="addBetModalLabel">
                    <svg class="w-5 h-5 text-primary-600 me-2 d-inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm cược mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('campaigns.bet', $campaign->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bet_date" class="form-label fw-medium text-gray-700">Ngày đặt cược</label>
                        <input type="date" name="bet_date" id="bet_date"
                               class="form-control form-control-lg"
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="bet_numbers" class="form-label fw-medium text-gray-700">Số lô (cách nhau bằng dấu phẩy)</label>
                        <input type="text" name="bet_numbers" id="bet_numbers"
                               class="form-control form-control-lg"
                               placeholder="VD: 18,25,36,05,72"
                               required>
                        <div class="form-text">Nhập các số lô từ 00-99, cách nhau bằng dấu phẩy</div>
                    </div>

                    <div class="mb-3">
                        <label for="bet_amount" class="form-label fw-medium text-gray-700">Số tiền cược (VNĐ)</label>
                        <input type="number" name="bet_amount" id="bet_amount"
                               class="form-control form-control-lg"
                               min="1000" step="1000"
                               placeholder="10,000" required>
                        <div class="form-text">Số tiền tối thiểu: 1,000 VNĐ</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Đóng
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Thêm cược
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Mobile-specific optimizations */
@media (max-width: 768px) {
    .touch-manipulation {
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }

    /* Improve button touch targets */
    .compass-btn-sm {
        min-height: 44px;
        padding: 12px 16px;
    }

    /* Better modal spacing on mobile */
    .modal-dialog {
        margin: 1rem;
    }

    .modal-content {
        border-radius: 12px;
    }

    /* Optimize card spacing */
    .compass-card {
        padding: 16px;
    }

    /* Better table on mobile */
    .table-responsive {
        font-size: 14px;
    }

    /* Smooth scroll for better mobile UX */
    html {
        scroll-behavior: smooth;
    }

    /* Prevent zoom on inputs */
    input[type="text"], input[type="number"], input[type="date"] {
        font-size: 16px;
    }

    /* Mobile-friendly badges */
    .compass-badge {
        font-size: 12px;
        padding: 4px 8px;
    }
}

/* Pull-to-refresh indicator */
@media (max-width: 768px) {
    .container {
        padding-top: 8px;
    }
}

/* Better focus states for mobile */
.compass-btn:focus,
.compass-btn:focus-visible {
    outline: 2px solid #3B82F6;
    outline-offset: 2px;
}
</style>

<script>
// Mobile-optimized functions with better UX
function runCampaign(id) {
    // Mobile-friendly confirmation
    if(window.confirm('🚀 Bạn có chắc muốn chạy chiến dịch này?')) {
        // Show loading state
        const button = event.target.closest('button');
        if(button) {
            button.disabled = true;
            button.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Đang chạy...';
        }
        // Implementation for running campaign
        setTimeout(() => window.location.reload(), 1000);
    }
}

function pauseCampaign(id) {
    if(window.confirm('⏸️ Bạn có chắc muốn tạm dừng chiến dịch này?')) {
        const button = event.target.closest('button');
        if(button) {
            button.disabled = true;
            button.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Đang dừng...';
        }
        setTimeout(() => window.location.reload(), 1000);
    }
}

function deleteCampaign(id) {
    if(window.confirm('🗑️ Bạn có chắc muốn xóa chiến dịch này?\n\n⚠️ Hành động này không thể hoàn tác!')) {
        const button = event.target.closest('button');
        if(button) {
            button.disabled = true;
            button.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Đang xóa...';
        }
        setTimeout(() => window.location.href = '/campaigns', 1000);
    }
}

// Bootstrap Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap not loaded!');
        return;
    }

    // Initialize modal
    const modalElement = document.getElementById('addBetModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });

        // Add click listeners to all "Thêm cược" buttons
        const betButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#addBetModal"]');
        betButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Opening bet modal...');
                modal.show();
            });
        });

        // Form validation
        const betForm = modalElement.querySelector('form');
        if (betForm) {
            betForm.addEventListener('submit', function(e) {
                const numbers = document.getElementById('bet_numbers').value;
                const amount = document.getElementById('bet_amount').value;

                if (!numbers || !amount) {
                    e.preventDefault();
                    alert('Vui lòng điền đầy đủ thông tin!');
                    return;
                }

                // Validate bet numbers format
                const numberArray = numbers.split(',').map(n => n.trim());
                const invalidNumbers = numberArray.filter(n => !/^\d{1,2}$/.test(n) || parseInt(n) > 99);

                if (invalidNumbers.length > 0) {
                    e.preventDefault();
                    alert('Số lô không hợp lệ: ' + invalidNumbers.join(', ') + '\nChỉ nhập số từ 00-99');
                    return;
                }

                // Show loading
                const submitBtn = betForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý...';
                }
            });
        }
    }

    // Add haptic feedback for mobile buttons (if supported)
    const buttons = document.querySelectorAll('.compass-btn, .btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (navigator.vibrate) {
                navigator.vibrate(50); // Short vibration for feedback
            }
        });
    });

    // Improve modal behavior on mobile
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            // Prevent background scrolling on mobile
            if (window.innerWidth <= 768) {
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            }
        });

        modal.addEventListener('hidden.bs.modal', function() {
            // Restore scrolling
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.width = '';
        });
    });

    // Add swipe gesture for mobile navigation
    let startX = null;
    let startY = null;

    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });

    document.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;

        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;

        const diffX = startX - endX;
        const diffY = startY - endY;

        // Swipe right to go back (minimum 100px, mostly horizontal)
        if (diffX < -100 && Math.abs(diffX) > Math.abs(diffY) * 2) {
            const backButton = document.querySelector('a[href*="campaigns"]:not([href*="/campaigns/"])');
            if (backButton && window.innerWidth <= 768) {
                backButton.click();
            }
        }

        startX = null;
        startY = null;
    });
});
</script>
@endsection
