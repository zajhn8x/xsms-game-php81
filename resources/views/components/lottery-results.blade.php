<table class="table table-bordered text-center">
    <tbody>
    <tr>
        <td class="fw-bold">🎯 Đặc biệt</td>
        <td class="fw-bold text-danger">{!! $highlightPrizes($prizes['special'] ?? 'N/A') !!}</td>
    </tr>
    <tr>
        <td class="fw-bold">🥇 Giải nhất</td>
        <td class="fw-bold text-success">{!! $highlightPrizes($prizes['prize1'] ?? 'N/A') !!}</td>
    </tr>
    <tr>
        <td>🥈 Giải nhì</td>
        <td>{!! $highlightPrizes($prizes['prize2'] ?? []) !!}</td>
    </tr>
    <tr>
        <td>🥉 Giải ba</td>
        <td>{!! $highlightPrizes($prizes['prize3'] ?? []) !!}</td>
    </tr>
    <tr>
        <td>🏅 Giải tư</td>
        <td>{!! $highlightPrizes($prizes['prize4'] ?? []) !!}</td>
    </tr>
    <tr>
        <td>🏆 Giải năm</td>
        <td>{!! $highlightPrizes($prizes['prize5'] ?? []) !!}</td>
    </tr>
    <tr>
        <td>🎟️ Giải sáu</td>
        <td>{!! $highlightPrizes($prizes['prize6'] ?? []) !!}</td>
    </tr>
    <tr>
        <td>🎫 Giải bảy</td>
        <td>{!! $highlightPrizes($prizes['prize7'] ?? []) !!}</td>
    </tr>
    </tbody>
</table>
{{--
<h5 class="mt-4 fw-bold text-center">📊 BẢNG ĐẦU – ĐUÔI LÔ TÔ</h5>

<div class="row">
    <!-- Bảng đầu -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-header text-center fw-bold bg-primary text-white">
                ĐẦU
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered text-center mb-0">
                    <thead>
                    <tr>
                        <th class="w-25">Đầu</th>
                        <th>Lô tô</th>
                    </tr>
                    </thead>
                    <tbody>
                    @for($i = 0; $i <= 9; $i++)
                        <tr>
                            <td class="fw-bold">{{ $i }}</td>
                            <td>
                                @if(!empty($heads[$i]))
                                    {!! implode('; ', $heads[$i]) !!};
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bảng đuôi -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-header text-center fw-bold bg-success text-white">
                ĐUÔI
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered text-center mb-0">
                    <thead>
                    <tr>
                        <th class="w-25">Đuôi</th>
                        <th>Lô tô</th>
                    </tr>
                    </thead>
                    <tbody>
                    @for($i = 0; $i <= 9; $i++)
                        <tr>
                            <td class="fw-bold">{{ $i }}</td>
                            <td>
                                @if(!empty($tails[$i]))
                                    {!! implode('; ', $tails[$i]) !!};
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
 --}}
