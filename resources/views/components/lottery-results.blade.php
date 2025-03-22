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
