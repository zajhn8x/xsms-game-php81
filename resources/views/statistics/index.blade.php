@extends('layouts.app')

@section('title', 'Thống kê')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📊 Thống Kê Tổng Hợp</h1>
                <p class="text-gray-600 mt-2">Báo cáo chi tiết về hoạt động đặt cược của bạn</p>
            </div>
            <div class="flex space-x-3">
                <button class="compass-btn compass-btn-secondary compass-btn-sm">
                    <i class="fas fa-download mr-2"></i>Xuất báo cáo
                </button>
                <a href="{{ route('dashboard') }}" class="compass-btn compass-btn-outline compass-btn-sm">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Tổng Chiến Dịch -->
        <div class="compass-card bg-gradient-to-br from-blue-50 to-blue-100">
            <div class="compass-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-blue-900">Tổng Chiến Dịch</h3>
                    <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                </div>
            </div>
            <div class="compass-card-body">
                <div class="text-3xl font-bold text-blue-900 mb-2">{{ $statistics['total_campaigns'] ?? 0 }}</div>
                <div class="text-sm text-blue-700">{{ $statistics['active_campaigns'] ?? 0 }} đang hoạt động</div>
            </div>
        </div>

        <!-- Tổng Cược -->
        <div class="compass-card bg-gradient-to-br from-green-50 to-green-100">
            <div class="compass-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-green-900">Tổng Cược</h3>
                    <i class="fas fa-coins text-green-600 text-2xl"></i>
                </div>
            </div>
            <div class="compass-card-body">
                <div class="text-3xl font-bold text-green-900 mb-2">{{ $statistics['total_bets'] ?? 0 }}</div>
                <div class="text-sm text-green-700">{{ $statistics['winning_bets'] ?? 0 }} thắng</div>
            </div>
        </div>

        <!-- Tỷ Lệ Thắng -->
        <div class="compass-card bg-gradient-to-br from-yellow-50 to-yellow-100">
            <div class="compass-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-yellow-900">Tỷ Lệ Thắng</h3>
                    <i class="fas fa-percentage text-yellow-600 text-2xl"></i>
                </div>
            </div>
            <div class="compass-card-body">
                <div class="text-3xl font-bold text-yellow-900 mb-2">{{ $statistics['win_rate'] ?? 0 }}%</div>
                <div class="text-sm text-yellow-700">Tỷ lệ thành công</div>
            </div>
        </div>

        <!-- Lợi Nhuận -->
        <div class="compass-card bg-gradient-to-br from-purple-50 to-purple-100">
            <div class="compass-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-purple-900">Lợi Nhuận</h3>
                    <i class="fas fa-chart-pie text-purple-600 text-2xl"></i>
                </div>
            </div>
            <div class="compass-card-body">
                <div class="text-3xl font-bold text-purple-900 mb-2">
                    @if(($statistics['profit_loss'] ?? 0) >= 0)
                        +{{ number_format($statistics['profit_loss'] ?? 0) }}đ
                    @else
                        {{ number_format($statistics['profit_loss'] ?? 0) }}đ
                    @endif
                </div>
                <div class="text-sm text-purple-700">
                    @if(($statistics['profit_loss'] ?? 0) >= 0)
                        <span class="text-green-600">📈 Lãi</span>
                    @else
                        <span class="text-red-600">📉 Lỗ</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="compass-card">
            <div class="compass-card-header">
                <h3 class="text-lg font-semibold">💰 Tài Chính</h3>
            </div>
            <div class="compass-card-body space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tổng đầu tư:</span>
                    <span class="font-semibold">{{ number_format($statistics['total_deposited'] ?? 0) }}đ</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Số dư hiện tại:</span>
                    <span class="font-semibold">{{ number_format($statistics['current_balance'] ?? 0) }}đ</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tổng tiền thắng:</span>
                    <span class="font-semibold text-green-600">{{ number_format($statistics['total_winnings'] ?? 0) }}đ</span>
                </div>
                <hr>
                <div class="flex justify-between text-lg font-bold">
                    <span>Lãi/Lỗ ròng:</span>
                    <span class="{{ ($statistics['profit_loss'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($statistics['profit_loss'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($statistics['profit_loss'] ?? 0) }}đ
                    </span>
                </div>
            </div>
        </div>

        <div class="compass-card">
            <div class="compass-card-header">
                <h3 class="text-lg font-semibold">📈 Hiệu Suất</h3>
            </div>
            <div class="compass-card-body space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tổng cược:</span>
                    <span class="font-semibold">{{ $statistics['total_bets'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cược thắng:</span>
                    <span class="font-semibold text-green-600">{{ $statistics['winning_bets'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tỷ lệ thắng:</span>
                    <span class="font-semibold">{{ $statistics['win_rate'] ?? 0 }}%</span>
                </div>
                <hr>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $statistics['win_rate'] ?? 0 }}%</div>
                    <div class="text-sm text-gray-500">Hiệu suất tổng thể</div>
                </div>
            </div>
        </div>

        <div class="compass-card">
            <div class="compass-card-header">
                <h3 class="text-lg font-semibold">🎯 Chiến Dịch</h3>
            </div>
            <div class="compass-card-body space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tổng chiến dịch:</span>
                    <span class="font-semibold">{{ $statistics['total_campaigns'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Đang hoạt động:</span>
                    <span class="font-semibold text-green-600">{{ $statistics['active_campaigns'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Đã hoàn thành:</span>
                    <span class="font-semibold text-blue-600">{{ $statistics['completed_campaigns'] ?? 0 }}</span>
                </div>
                <hr>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $statistics['active_campaigns'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Đang chạy</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent History --}}
    <div class="compass-card">
        <div class="compass-card-header">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">📋 Lịch Sử Cược Gần Đây</h3>
                <a href="{{ route('campaigns.index') }}" class="compass-btn compass-btn-sm compass-btn-outline">
                    Xem tất cả
                </a>
            </div>
        </div>
        <div class="compass-card-body">
            @if($history && $history->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ngày
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Số Lô
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Điểm
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Số Tiền
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kết Quả
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($history as $bet)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($bet->bet_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $bet->lo_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $bet->points }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($bet->amount) }}đ
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($bet->is_win)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✅ Thắng +{{ number_format($bet->win_amount) }}đ
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                ❌ Thua
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($history->hasPages())
                    <div class="mt-6">
                        {{ $history->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📊</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có dữ liệu</h3>
                    <p class="text-gray-600 mb-4">Bạn chưa có cược nào trong 30 ngày qua.</p>
                    <a href="{{ route('campaigns.create') }}" class="compass-btn compass-btn-primary">
                        Tạo chiến dịch đầu tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
