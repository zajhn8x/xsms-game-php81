@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-12">
    {{-- Dashboard Header --}}
    <div class="compass-fade-in">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-4xl font-bold compass-text-gradient">Dashboard</h1>
                <p class="mt-1 text-gray-600">Tổng quan tài khoản và hoạt động của bạn trong thời gian thực.</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-2">
                <a href="{{ route('campaigns.create') }}" class="compass-btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tạo chiến dịch mới
                </a>
                <a href="{{ route('wallet.index') }}" class="compass-btn-secondary">
                    Nạp tiền
                </a>
            </div>
        </div>
    </div>

    {{-- Wallet & Campaign Summary Cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 compass-slide-up">

        {{-- Wallet Summary --}}
        <div class="lg:col-span-4 space-y-6">
            <h2 class="text-xl font-semibold text-gray-800">Tổng quan Ví</h2>

            <div class="compass-stat-card">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-100 rounded-lg">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <p class="compass-stat-label">Tổng số dư</p>
                        <p class="compass-stat-value">{{ number_format($dashboardData['wallet_summary']['total_balance']) }} <span class="text-base font-medium text-gray-500">VND</span></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="compass-card p-4">
                    <p class="text-sm text-gray-600">Ví thật</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($dashboardData['wallet_summary']['real_balance']) }}</p>
                </div>
                <div class="compass-card p-4">
                    <p class="text-sm text-gray-600">Ví bonus</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($dashboardData['wallet_summary']['bonus_balance']) }}</p>
                </div>
            </div>
        </div>

        {{-- Campaign Summary --}}
        <div class="lg:col-span-8 space-y-6">
            <h2 class="text-xl font-semibold text-gray-800">Tổng quan Chiến dịch</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="compass-stat-card">
                    <p class="compass-stat-label">Tổng chiến dịch</p>
                    <p class="compass-stat-value">{{ $dashboardData['campaign_overview']['total_campaigns'] }}</p>
                    <a href="{{ route('campaigns.index') }}" class="text-sm text-primary-600 hover:underline mt-2 inline-block">Xem tất cả</a>
                </div>
                <div class="compass-stat-card">
                    <p class="compass-stat-label">Đang hoạt động</p>
                    <p class="compass-stat-value text-success-600">{{ $dashboardData['campaign_overview']['active_campaigns'] }}</p>
                    <div class="flex items-center justify-center mt-2">
                        <span class="w-2 h-2 bg-success-500 rounded-full mr-1.5 animate-pulse"></span>
                        <span class="text-sm text-gray-600">Live</span>
                    </div>
                </div>
                <div class="compass-stat-card">
                    <p class="compass-stat-label">Đã hoàn thành</p>
                    <p class="compass-stat-value text-info-600">{{ $dashboardData['campaign_overview']['completed_campaigns'] }}</p>
                    <a href="{{ route('campaigns.index', ['status' => 'completed']) }}" class="text-sm text-primary-600 hover:underline mt-2 inline-block">Xem kết quả</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="compass-slide-up">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Phân tích Hiệu suất</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- User Activity Chart --}}
            <div class="compass-chart-container">
                <div class="compass-chart-header">
                    <h3 class="compass-chart-title">Hoạt động 7 ngày qua</h3>
                </div>
                <canvas id="userActivityChart"></canvas>
            </div>

            {{-- Campaign Performance Chart --}}
            <div class="compass-chart-container">
                <div class="compass-chart-header">
                    <h3 class="compass-chart-title">Hiệu suất Chiến dịch</h3>
                </div>
                <canvas id="campaignPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Campaigns & Activities --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 compass-slide-up">
        {{-- Recent Campaigns --}}
        <div class="lg:col-span-7">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Chiến dịch gần đây</h2>
            <div class="compass-table">
                <table>
                    <thead>
                        <tr>
                            <th>Tên chiến dịch</th>
                            <th>Trạng thái</th>
                            <th>Ngân sách</th>
                            <th>Lợi nhuận</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboardData['recent_campaigns'] as $campaign)
                            <tr>
                                <td>
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="font-medium text-primary-600 hover:underline">{{ $campaign->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $campaign->created_at->format('d/m/Y') }}</p>
                                </td>
                                <td>
                                    @if($campaign->status == 'active')
                                        <span class="campaign-status-active">Đang chạy</span>
                                    @elseif($campaign->status == 'paused')
                                        <span class="campaign-status-paused">Tạm dừng</span>
                                    @else
                                        <span class="campaign-status-completed">Hoàn thành</span>
                                    @endif
                                </td>
                                <td>{{ number_format($campaign->budget) }}</td>
                                <td class="{{ $campaign->profit > 0 ? 'text-success-600' : 'text-error-600' }}">
                                    {{ number_format($campaign->profit) }}
                                </td>
                                <td>
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="compass-btn-secondary text-xs py-1 px-3">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">
                                    Chưa có chiến dịch nào.
                                    <a href="{{ route('campaigns.create') }}" class="text-primary-600 font-semibold hover:underline">Tạo ngay!</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="lg:col-span-5">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Hoạt động gần đây</h2>
            <div class="compass-card">
                <ul class="divide-y divide-gray-100">
                    @forelse($dashboardData['recent_activities'] as $activity)
                        <li class="p-4 flex items-start">
                            <div class="p-2 bg-gray-100 rounded-full mr-4">
                                @if(str_contains($activity->description, 'tạo'))
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                @elseif(str_contains($activity->description, 'nạp'))
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="p-4 text-center text-gray-500">Chưa có hoạt động nào.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userActivityChartCtx = document.getElementById('userActivityChart')?.getContext('2d');
    const campaignPerformanceChartCtx = document.getElementById('campaignPerformanceChart')?.getContext('2d');

    if (userActivityChartCtx) {
        new Chart(userActivityChartCtx, {
            type: 'line',
            data: {
                labels: @json($dashboardData['charts']['user_activity']['labels']),
                datasets: [{
                    label: 'Hoạt động',
                    data: @json($dashboardData['charts']['user_activity']['data']),
                    borderColor: 'rgb(8, 145, 178)',
                    backgroundColor: 'rgba(8, 145, 178, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    if (campaignPerformanceChartCtx) {
        new Chart(campaignPerformanceChartCtx, {
            type: 'bar',
            data: {
                labels: @json($dashboardData['charts']['campaign_performance']['labels']),
                datasets: [{
                    label: 'Lợi nhuận',
                    data: @json($dashboardData['charts']['campaign_performance']['data']),
                    backgroundColor: @json(collect($dashboardData['charts']['campaign_performance']['data'])->map(fn($v) => $v >= 0 ? 'rgba(34, 197, 94, 0.7)' : 'rgba(239, 68, 68, 0.7)')->toArray()),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
