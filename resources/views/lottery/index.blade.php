@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4 text-center text-primary">📌 KẾT QUẢ XỔ SỐ</h2>

        @if($results->isNotEmpty())
            @foreach($results as $result)
                @php
                    $prizes = $result->prizes;
                    $lo_array = $result->lo_array;

                    $formatPrize = fn($prize) => is_array($prize) ? implode(', ', $prize) : $prize;

                    $lo_head = [];
                    $lo_tail = [];
                    foreach ($lo_array as $lo) {
                        $head = substr($lo, 0, 1);
                        $tail = substr($lo, -1);
                        $lo_head[$head][] = $lo;
                        $lo_tail[$tail][] = $lo;
                    }
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
                            </tbody>
                        </table>

                        <h5 class="text-center mt-4 fw-bold text-primary">🔢 LÔ TÔ</h5>
                        <table class="table table-bordered text-center">
                            <tr>
                                @foreach($lo_array as $lo)
                                    <td>{{ $lo }}</td>
                                @endforeach
                            </tr>
                        </table>

                        <h5 class="text-center mt-4 fw-bold text-primary">📊 THỐNG KÊ LÔ TÔ</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Đầu số</h6>
                                <table class="table table-bordered text-center">
                                    @foreach(range(0, 9) as $num)
                                        <tr>
                                            <td class="fw-bold">{{ $num }}</td>
                                            <td>{{ isset($lo_head[$num]) ? implode('; ', $lo_head[$num]) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Đuôi số</h6>
                                <table class="table table-bordered text-center">
                                    @foreach(range(0, 9) as $num)
                                        <tr>
                                            <td class="fw-bold">{{ $num }}</td>
                                            <td>{{ isset($lo_tail[$num]) ? implode('; ', $lo_tail[$num]) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-center text-muted">⛔ Không có kết quả nào.</p>
        @endif
    </div>
@endsection
