
@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Thống Kê Cá Nhân</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tổng số lần cược:</span>
                        <strong>{{ $stats['total_bets'] }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tổng tiền đã cược:</span>
                        <strong>{{ number_format($stats['total_amount_bet']) }}đ</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Số lần trúng:</span>
                        <strong>{{ $stats['total_wins'] }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tổng tiền thắng:</span>
                        <strong>{{ number_format($stats['total_winnings']) }}đ</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
