@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Chi tiết chiến dịch</h5>
                        <div>
                            @if($campaign->status === 'active')
                                <a href="{{ route('campaigns.bet.form', $campaign) }}" class="btn btn-primary me-2">Đặt
                                    cược</a>
                            @endif
                            <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Quay lại</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Thông tin cơ bản</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Ngày bắt đầu:</th>
                                        <td>{{ $campaign->start_date->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Số ngày:</th>
                                        <td>{{ $campaign->days }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vốn ban đầu:</th>
                                        <td>{{ number_format($campaign->initial_balance) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vốn hiện tại:</th>
                                        <td>{{ number_format($campaign->current_balance) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Trạng thái:</th>
                                        <td>{{ $campaign->status }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Thống kê cược</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Tổng số cược:</th>
                                        <td>{{ $stats['total_bets'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Số cược thắng:</th>
                                        <td>{{ $stats['win_bets'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tỷ lệ thắng:</th>
                                        <td>{{ number_format($campaign->win_rate, 1) }}%</td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tiền cược:</th>
                                        <td>{{ number_format($campaign->total_bet_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tiền thắng:</th>
                                        <td>{{ number_format($campaign->total_win_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Lãi/Lỗ:</th>
                                        <td class="{{ $campaign->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($campaign->profit) }}
                                            ({{ number_format($campaign->profit_rate, 1) }}%)
                                        </td>
                                    </tr>
                                </table>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Tổng số lần cược:</th>
                                        <td>{{ $stats['total_bets'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Số lần thắng:</th>
                                        <td>{{ $stats['win_bets'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tỷ lệ thắng:</th>
                                        <td>{{ $stats['total_bets'] > 0 ? number_format(($stats['win_bets'] / $stats['total_bets']) * 100, 1) : 0 }}
                                            %
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tiền đã cược:</th>
                                        <td>{{ number_format($stats['total_bet_amount']) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tiền thắng:</th>
                                        <td>{{ number_format($stats['total_win_amount']) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Lãi/Lỗ:</th>
                                        @php
                                            $profit = $stats['total_win_amount'] - $stats['total_bet_amount'];
                                            $profitClass = $profit >= 0 ? 'text-success' : 'text-danger';
                                        @endphp
                                        <td class="{{ $profitClass }}">{{ number_format($profit) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6>Lịch sử cược gần đây</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Ngày cược</th>
                                        <th>Số lô</th>
                                        <th>Điểm</th>
                                        <th>Tiền cược</th>
                                        <th>Kết quả</th>
                                        <th>Tiền thắng</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($bets as $bet)
                                        <tr>
                                            <td>{{ $bet->bet_date->format('d/m/Y') }}</td>
                                            <td>{{ str_pad($bet->lo_number, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $bet->points }}</td>
                                            <td>{{ number_format($bet->amount) }}</td>
                                            <td>
                                                @if($bet->status === 'pending')
                                                    <span class="badge bg-warning">Chờ kết quả</span>
                                                @else
                                                    @if($bet->is_win)
                                                        <span class="badge bg-success">Thắng</span>
                                                    @else
                                                        <span class="badge bg-danger">Thua</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $bet->is_win ? number_format($bet->win_amount) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $bets->links() }}
                        </div>

                        <h6>Lịch sử cược</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Ngày cược</th>
                                    <th>Số lô</th>
                                    <th>Điểm</th>
                                    <th>Tiền cược</th>
                                    <th>Kết quả</th>
                                    <th>Tiền thắng</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bets as $bet)
                                    <tr>
                                        <td>{{ $bet->bet_date->format('d/m/Y') }}</td>
                                        <td>{{ $bet->lo_number }}</td>
                                        <td>{{ $bet->points }}</td>
                                        <td>{{ number_format($bet->amount) }}</td>
                                        <td>
                                            @if($bet->status === 'pending')
                                                <span class="badge bg-warning">Chờ kết quả</span>
                                            @else
                                                @if($bet->is_win)
                                                    <span class="badge bg-success">Thắng</span>
                                                @else
                                                    <span class="badge bg-danger">Thua</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $bet->win_amount ? number_format($bet->win_amount) : '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $bets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
