@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4 text-center text-primary">📌 KẾT QUẢ XỔ SỐ</h2>

        @if($results->isNotEmpty())
            @foreach($results as $result)
                @php
                    $prizes = $result->prizes;
                    $lo_array = $result->lo_array;

                    // Hàm chuẩn hóa số
                    $formatPrize = fn($prize) => is_array($prize) ? implode(', ', $prize) : $prize;
                @endphp

                <div class="card mb-4 shadow-sm">
                    <div class="card-header text-white bg-dark fw-bold">
                        📅 Ngày {{ \Carbon\Carbon::parse($result->draw_date)->format('d/m/Y') }}
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered text-center">
                            <tbody>
                            <tr><td class="fw-bold">🎯 Đặc biệt</td> <td class="fw-bold text-danger">{{ $formatPrize($prizes['special'] ?? 'N/A') }}</td></tr>
                            <tr><td class="fw-bold">🥇 Giải nhất</td> <td class="fw-bold text-success">{{ $formatPrize($prizes['prize1'] ?? 'N/A') }}</td></tr>
                            <tr><td>🥈 Giải nhì</td> <td>{{ $formatPrize($prizes['prize2'] ?? []) }}</td></tr>
                            <tr><td>🥉 Giải ba</td> <td>{{ $formatPrize($prizes['prize3'] ?? []) }}</td></tr>
                            <tr><td>🏅 Giải tư</td> <td>{{ $formatPrize($prizes['prize4'] ?? []) }}</td></tr>
                            <tr><td>🏆 Giải năm</td> <td>{{ $formatPrize($prizes['prize5'] ?? []) }}</td></tr>
                            <tr><td>🎟️ Giải sáu</td> <td>{{ $formatPrize($prizes['prize6'] ?? []) }}</td></tr>
                            <tr><td>🎫 Giải bảy</td> <td>{{ $formatPrize($prizes['prize7'] ?? []) }}</td></tr>
                            <tr><td class="fw-bold">🔢 Lô tô</td> <td class="fw-bold text-primary">{{ implode(', ', $lo_array) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-center text-muted">⛔ Không có kết quả nào.</p>
        @endif
    </div>
@endsection
