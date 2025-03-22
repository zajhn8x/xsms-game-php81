
@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Timeline Cầu Lô #{{ $cauLo->id }}</h2>

    <!-- Meta Information -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thông tin Cầu Lô</h5>
        </div>
        <div class="card-body">
            <p><strong>Tên công thức:</strong> {{ $meta['formula_name'] }}</p>
            <p><strong>Cấu trúc:</strong> <pre class="bg-light p-2">{{ json_encode($meta['formula_structure'], JSON_PRETTY_PRINT) }}</pre></p>
            <p><strong>Tỷ lệ trúng:</strong> {{ number_format($meta['hit_rate'], 2) }}%</p>
            <p><strong>Tổng số lần trúng:</strong> {{ $meta['total_hits'] }}</p>
        </div>
    </div>

    <!-- Timeline -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Lịch sử trúng (30 ngày gần nhất)</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                @foreach($dateRange as $date)
                    <div class="timeline-item mb-3 p-3 border rounded {{ isset($hits[$date]) ? 'bg-success bg-opacity-10' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h6>
                            @if(isset($hits[$date]))
                                <span class="badge bg-success">Trúng: {{ $hits[$date]->so_trung }}</span>
                            @else
                                <span class="badge bg-secondary">Không trúng</span>
                            @endif
                        </div>
                        @if(isset($results[$date]))
                            <small class="text-muted">Kết quả: {{ $results[$date]->result_string }}</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    max-height: 600px;
    overflow-y: auto;
}

.timeline-item {
    position: relative;
    margin-left: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 50%;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6c757d;
    transform: translateY(-50%);
}

.timeline-item.bg-success-light::before {
    background: #198754;
}
</style>
@endsection
