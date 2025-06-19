@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Dashboard</h1>
        </div>
    </div>

    <!-- Wallet Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ví Thật</h5>
                    <h3 class="text-primary">{{ number_format($dashboardData['wallet_summary']['real_balance']) }} VND</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ví Ảo</h5>
                    <h3 class="text-info">{{ number_format($dashboardData['wallet_summary']['virtual_balance']) }} VND</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ví Bonus</h5>
                    <h3 class="text-warning">{{ number_format($dashboardData['wallet_summary']['bonus_balance']) }} VND</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tổng Cộng</h5>
                    <h3 class="text-success">{{ number_format($dashboardData['wallet_summary']['total_balance']) }} VND</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tổng Chiến Dịch</h5>
                    <h3>{{ $dashboardData['campaign_overview']['total_campaigns'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Đang Hoạt Động</h5>
                    <h3 class="text-success">{{ $dashboardData['campaign_overview']['active_campaigns'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Đã Hoàn Thành</h5>
                    <h3 class="text-info">{{ $dashboardData['campaign_overview']['completed_campaigns'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Chiến Dịch Công Khai</h5>
                    <h3 class="text-warning">{{ $dashboardData['campaign_overview']['public_campaigns'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Hiệu Suất Tổng Quan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Tổng Đầu Tư:</strong></p>
                            <h4 class="text-primary">{{ number_format($dashboardData['performance_metrics']['total_invested']) }} VND</h4>
                        </div>
                        <div class="col-6">
                            <p><strong>Giá Trị Hiện Tại:</strong></p>
                            <h4 class="text-info">{{ number_format($dashboardData['performance_metrics']['current_value']) }} VND</h4>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Lợi Nhuận:</strong></p>
                            <h4 class="{{ $dashboardData['performance_metrics']['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($dashboardData['performance_metrics']['total_profit']) }} VND
                                ({{ $dashboardData['performance_metrics']['profit_percentage'] }}%)
                            </h4>
                        </div>
                        <div class="col-6">
                            <p><strong>Tỉ Lệ Thắng:</strong></p>
                            <h4 class="text-warning">{{ $dashboardData['performance_metrics']['win_rate'] }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Biểu Đồ Lợi Nhuận</h5>
                </div>
                <div class="card-body">
                    <canvas id="profitChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Giao Dịch Gần Đây</h5>
                </div>
                <div class="card-body">
                    @if(count($dashboardData['recent_activities']['recent_transactions']) > 0)
                        @foreach($dashboardData['recent_activities']['recent_transactions'] as $transaction)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ ucfirst($transaction['type']) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $transaction['created_at']->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-{{ $transaction['status'] === 'completed' ? 'success' : 'warning' }}">
                                        {{ number_format($transaction['amount']) }} VND
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">Chưa có giao dịch nào.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Cược Gần Đây</h5>
                </div>
                <div class="card-body">
                    @if(count($dashboardData['recent_activities']['recent_bets']) > 0)
                        @foreach($dashboardData['recent_activities']['recent_bets'] as $bet)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ $bet['campaign_name'] }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $bet['created_at']->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-{{ $bet['is_win'] ? 'success' : 'danger' }}">
                                        {{ $bet['is_win'] ? '+' : '' }}{{ number_format($bet['profit']) }} VND
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">Chưa có cược nào.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Thao Tác Nhanh</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('wallet.index') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-wallet"></i> Quản Lý Ví
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('campaigns.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus"></i> Tạo Chiến Dịch
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('historical-testing.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-history"></i> Test Lịch Sử
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('campaigns.index') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-list"></i> Xem Chiến Dịch
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profit Chart
    const profitCtx = document.getElementById('profitChart').getContext('2d');
    const profitData = @json($dashboardData['charts_data']['profit_loss_chart']);

    new Chart(profitCtx, {
        type: 'line',
        data: {
            labels: profitData.map(item => item.date),
            datasets: [{
                label: 'Lợi Nhuận',
                data: profitData.map(item => item.profit),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' VND';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
