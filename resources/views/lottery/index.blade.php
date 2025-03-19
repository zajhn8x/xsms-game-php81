
@extends('layouts.app')

@section('content')
<div class="lottery-container">
    <h1 class="lottery-title">Kết quả Xổ số Miền Bắc {{ $date ?? 'Hôm nay' }}</h1>

    <div class="filters">
        <select id="dayFilter" class="form-select">
            <option value="10" {{ $days == 10 ? 'selected' : '' }}>10 ngày gần nhất</option>
            <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 ngày gần nhất</option>
            <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 ngày gần nhất</option>
        </select>

        <div class="date-range">
            <input type="date" id="startDate" class="form-control">
            <input type="date" id="endDate" class="form-control">
            <button id="filterBtn" class="btn btn-primary">Lọc</button>
        </div>
    </div>

    <div class="result-table">
        <table class="table table-bordered">
            <tr><th class="prize-header">Đặc biệt</th><td class="prize-number special">{{ $result->special_prize ?? '' }}</td></tr>
            <tr><th class="prize-header">Giải nhất</th><td class="prize-number">{{ $result->first_prize ?? '' }}</td></tr>
            <tr><th class="prize-header">Giải nhì</th><td class="prize-number">{{ $result->second_prize ?? '' }}</td></tr>
            <tr>
                <th class="prize-header">Giải ba</th>
                <td class="prize-number">
                    @foreach(explode(',', $result->third_prize ?? '') as $num)
                        <span>{{ $num }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th class="prize-header">Giải tư</th>
                <td class="prize-number">
                    @foreach(explode(',', $result->fourth_prize ?? '') as $num)
                        <span>{{ $num }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th class="prize-header">Giải năm</th>
                <td class="prize-number">
                    @foreach(explode(',', $result->fifth_prize ?? '') as $num)
                        <span>{{ $num }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th class="prize-header">Giải sáu</th>
                <td class="prize-number">
                    @foreach(explode(',', $result->sixth_prize ?? '') as $num)
                        <span>{{ $num }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th class="prize-header">Giải bảy</th>
                <td class="prize-number">
                    @foreach(explode(',', $result->seventh_prize ?? '') as $num)
                        <span>{{ $num }}</span>
                    @endforeach
                </td>
            </tr>
        </table>
    </div>
</div>

@push('styles')
<style>
.lottery-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.lottery-title {
    text-align: center;
    margin-bottom: 20px;
}

.filters {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.date-range {
    display: flex;
    gap: 10px;
}

.result-table table {
    width: 100%;
    border-collapse: collapse;
}

.prize-header {
    width: 120px;
    background: #f5f5f5;
    text-align: center;
}

.prize-number {
    font-size: 24px;
    text-align: center;
}

.prize-number span {
    display: inline-block;
    margin: 0 10px;
}

.special {
    color: red;
    font-weight: bold;
    font-size: 32px;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('dayFilter').addEventListener('change', function() {
    window.location.href = `/lottery?days=${this.value}`;
});

document.getElementById('filterBtn').addEventListener('click', function() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    if(start && end) {
        window.location.href = `/lottery?start_date=${start}&end_date=${end}`;
    }
});
</script>
@endpush
@endsection
