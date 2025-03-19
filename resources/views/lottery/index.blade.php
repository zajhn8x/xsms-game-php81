
@extends('layouts.app')

@section('content')
<div class="lottery-container">
    <h1 class="lottery-title">Kết quả Xổ số Miền Bắc {{ now()->format('d-m-Y') }}</h1>

    <div class="result-filters">
        <select id="dayFilter" class="form-select">
            <option value="today">Hôm nay</option>
            <option value="yesterday">Hôm qua</option>
            <option value="7days">7 ngày qua</option>
        </select>
    </div>

    <div class="result-table">
        <table class="table table-bordered">
            <tr>
                <td class="prize-header">Đặc biệt</td>
                <td class="prize-number special">{{ $results->special_prize ?? '48130' }}</td>
            </tr>
            <tr>
                <td class="prize-header">Giải nhất</td>
                <td class="prize-number">{{ $results->first_prize ?? '66421' }}</td>
            </tr>
            <tr>
                <td class="prize-header">Giải nhì</td>
                <td class="prize-number">
                    <span>{{ $results->second_prize[0] ?? '73844' }}</span>
                    <span>{{ $results->second_prize[1] ?? '41421' }}</span>
                </td>
            </tr>
            <tr>
                <td class="prize-header">Giải ba</td>
                <td class="prize-number">
                    @foreach($results->third_prize ?? ['62423', '46621', '17961', '19630', '55272', '97320'] as $prize)
                        <span>{{ $prize }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="prize-header">Giải tư</td>
                <td class="prize-number">
                    @foreach($results->fourth_prize ?? ['9526', '7565', '2651', '1660'] as $prize)
                        <span>{{ $prize }}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="prize-header">Giải năm</td>
                <td class="prize-number">
                    @foreach($results->fifth_prize ?? ['9130', '1718', '4336'] as $prize)
                        <span>{{ $prize }}</span>
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
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.lottery-title {
    text-align: center;
    color: #d10000;
    font-size: 24px;
    margin-bottom: 20px;
}

.result-filters {
    margin-bottom: 20px;
    text-align: center;
}

.result-table table {
    width: 100%;
    border-collapse: collapse;
}

.prize-header {
    width: 120px;
    background: #f5f5f5;
    text-align: center;
    font-weight: bold;
    padding: 10px;
}

.prize-number {
    font-size: 24px;
    text-align: center;
    padding: 10px;
}

.prize-number span {
    display: inline-block;
    margin: 0 10px;
}

.special {
    color: #d10000;
    font-weight: bold;
    font-size: 32px;
}

#dayFilter {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('dayFilter').addEventListener('change', function() {
    // Handle date filter change
    let date = this.value;
    window.location.href = `/lottery?date=${date}`;
});
</script>
@endpush
@endsection
