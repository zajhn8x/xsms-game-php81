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
