@extends('layouts.app')

@section('title', 'Quản Lý Ví')

@push('styles')
<style>
    .wallet-status-completed {
        background-color: #E6F4EA;
        color: #34A853;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    .wallet-status-pending {
        background-color: #FFF3E2;
        color: #FBBC04;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    .wallet-status-failed {
        background-color: #FCE8E6;
        color: #EA4335;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    .wallet-status-deposit {
        background-color: #E8F0FE;
        color: #4285F4;
    }
     .wallet-status-withdraw {
        background-color: #FCE8E6;
        color: #EA4335;
    }
     .wallet-status-transfer {
        background-color: #EFEFEF;
        color: #5F6368;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="compass-fade-in mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Ví của tôi</h1>
                <p class="mt-1 text-gray-600">Quản lý số dư, nạp, rút và xem lịch sử giao dịch.</p>
            </div>
            {{-- Action Buttons --}}
            <div class="mt-4 sm:mt-0 flex items-center space-x-2">
                <button class="compass-btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nạp tiền
                </button>
                 <button class="compass-btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 20v-5h-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9l5-5L20 15"></path></svg>
                    Chuyển đổi
                </button>
            </div>
        </div>
    </div>

    {{-- Wallet Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 compass-slide-up">
        <div class="compass-stat-card border-l-4 border-green-500">
            <div class="compass-stat-value">{{ number_format($wallet->real_balance) }} <span class="text-lg font-medium">VND</span></div>
            <div class="compass-stat-label">Ví Thật</div>
            <p class="text-xs text-gray-500 mt-1">Số dư có thể rút</p>
        </div>
        <div class="compass-stat-card border-l-4 border-blue-500">
            <div class="compass-stat-value">{{ number_format($wallet->virtual_balance) }} <span class="text-lg font-medium">VND</span></div>
            <div class="compass-stat-label">Ví Ảo</div>
             <p class="text-xs text-gray-500 mt-1">Dùng để thử nghiệm</p>
        </div>
        <div class="compass-stat-card border-l-4 border-yellow-500">
            <div class="compass-stat-value">{{ number_format($wallet->bonus_balance) }} <span class="text-lg font-medium">VND</span></div>
            <div class="compass-stat-label">Ví Bonus</div>
             <p class="text-xs text-gray-500 mt-1">Số dư từ khuyến mãi</p>
        </div>
        <div class="compass-stat-card border-l-4 border-gray-400">
            <div class="compass-stat-value">{{ number_format($wallet->total_balance) }} <span class="text-lg font-medium">VND</span></div>
            <div class="compass-stat-label">Tổng cộng</div>
             <p class="text-xs text-gray-500 mt-1">Tổng số dư các ví</p>
        </div>
    </div>

    {{-- Transaction History --}}
    <div class="compass-card compass-slide-up">
        <div class="compass-card-header">
            <h3 class="text-lg font-semibold">Lịch sử giao dịch gần đây</h3>
            <a href="#" class="compass-link">Xem tất cả</a>
        </div>
        <div class="compass-card-body p-0">
            <div class="overflow-x-auto">
                <div class="compass-table">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="compass-table th">Thời gian</th>
                                <th class="compass-table th">Loại</th>
                                <th class="compass-table th text-right">Số tiền</th>
                                <th class="compass-table th text-center">Trạng thái</th>
                                <th class="compass-table th">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td class="compass-table td">
                                        <div class="font-medium">{{ $transaction->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td class="compass-table td">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full wallet-status-{{ strtolower($transaction->type) }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="compass-table td text-right font-mono {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->amount > 0 ? '+' : '-' }} {{ number_format(abs($transaction->amount)) }} VND
                                    </td>
                                    <td class="compass-table td text-center">
                                        <span class="wallet-status-{{ strtolower($transaction->status) }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td class="compass-table td text-sm text-gray-600">
                                        {{ $transaction->description }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Không có giao dịch</h3>
                                        <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách nạp tiền vào tài khoản của bạn.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="compass-card-footer">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
{{-- Modals for deposit, withdraw, transfer are removed for now to simplify. Will be re-implemented with AlpineJS if needed. --}}
@endsection
