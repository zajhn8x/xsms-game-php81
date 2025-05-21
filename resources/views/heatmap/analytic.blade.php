@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-center">Phân Tích Heatmap</h2>
            <p class="text-center text-muted">
                Ngày: {{ $currentDate->format('d/m/Y') }}
            </p>
        </div>
    </div>

    {{-- Date Navigation --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @php
                                $prevDate = $currentDate->copy()->subDay();
                                $nextDate = $currentDate->copy()->addDay();
                            @endphp
                            <a href="{{ route('heatmap.analytic', $prevDate->format('Y-m-d')) }}" class="btn btn-outline-primary">
                                <i class="fas fa-chevron-left"></i> Ngày trước
                            </a>
                        </div>
                        <div class="flex-grow-1 mx-3">
                            <select class="form-select" onchange="window.location.href = '{{ route('heatmap.analytic', '') }}/' + this.value">
                                @foreach($availableDates as $date)
                                    <option value="{{ $date['value'] }}" {{ $date['value'] === $currentDate->format('Y-m-d') ? 'selected' : '' }}>
                                        {{ $date['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <a href="{{ route('heatmap.analytic', $nextDate->format('Y-m-d')) }}" class="btn btn-outline-primary">
                                Ngày sau <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        {{-- Type Filter --}}
                        <div class="col-md-4">
                            <label class="form-label">Loại</label>
                            <select name="type" class="form-select">
                                <option value="">Tất cả</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        @switch($type)
                                            @case('long_run')
                                                Long Run
                                                @break
                                            @case('long_run_stop')
                                                Long Run Stop
                                                @break
                                            @case('rebound_after_long_run')
                                                Rebound
                                                @break
                                        @endswitch
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Score Filter --}}
                        <div class="col-md-3">
                            <label class="form-label">Điểm tối thiểu</label>
                            <input type="number" name="min_score" class="form-control" step="0.1" value="{{ request('min_score') }}">
                        </div>

                        {{-- Streak Length Filter --}}
                        <div class="col-md-3">
                            <label class="form-label">Streak tối thiểu</label>
                            <input type="number" name="min_streak" class="form-control" value="{{ request('min_streak') }}">
                        </div>

                        {{-- Hit Status Filter --}}
                        <div class="col-md-2">
                            <label class="form-label">Trạng thái</label>
                            <select name="hit_status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="true" {{ request('hit_status') === 'true' ? 'selected' : '' }}>Trúng</option>
                                <option value="false" {{ request('hit_status') === 'false' ? 'selected' : '' }}>Không trúng</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                            <a href="{{ route('heatmap.analytic', $currentDate->format('Y-m-d')) }}" class="btn btn-secondary">Reset</a>
                            <button type="button" class="btn btn-info ms-2" id="showHitStatusBtn">Hiển thị trạng thái trúng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Streak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Công thức</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($insights as $insight)
                                    <tr class="
                                        {{ $insight->type === 'long_run' ? 'table-warning' : ($insight->type === 'long_run_stop' ? 'table-danger' : 'table-success') }}
                                        hit-status-row
                                        {{ $insight->hit === null ? 'hit-null-row' : 'hit-not-null-row' }}
                                    ">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $insight->formula_id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @switch($insight->type)
                                                @case('long_run')
                                                    <span class="badge bg-warning">Long Run</span>
                                                    @break
                                                @case('long_run_stop')
                                                    <span class="badge bg-danger">Long Run Stop</span>
                                                    @break
                                                @case('rebound_after_long_run')
                                                    <span class="badge bg-success">Rebound</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{-- Hiển thị streak với badge màu riêng --}}
                                            @php
                                                $streak = $insight->processed_extra['streak_length'] ?? null;
                                            @endphp
                                            @if($streak !== null)
                                                @if($streak >= 6)
                                                    <span class="badge bg-success">Streak: {{ $streak }}</span>
                                                @elseif($streak == 4 || $streak == 5)
                                                    <span class="badge bg-warning text-dark">Streak: {{ $streak }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Streak: {{ $streak }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($insight->type === 'rebound_after_long_run')
                                                <div class="d-flex flex-column gap-1 mt-1">
                                                    @if($insight->processed_extra['step_1'] ?? false)
                                                        <span class="badge bg-success">Step 1: Đã dừng</span>
                                                    @endif
                                                    @if($insight->processed_extra['step_2'] ?? false)
                                                        <span class="badge bg-warning">Step 2: Đang chờ</span>
                                                    @endif
                                                    @if($insight->processed_extra['rebound_success'] ?? false)
                                                        <span class="badge bg-primary">Rebound thành công</span>
                                                    @endif
                                                </div>
                                            @elseif($insight->type === 'long_run')
                                                <span class="badge bg-warning text-dark mt-1">Long Run</span>
                                            @elseif($insight->type === 'long_run_stop')
                                                <span class="badge bg-danger mt-1">Dừng: {{ $insight->processed_extra['day_stop'] ?? 0 }} ngày</span>
                                            @endif
                                            {{-- Predicted values by position --}}
                                            @if(!empty($insight->processed_extra['predicted_values_by_position']))
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach($insight->processed_extra['predicted_values_by_position'] as $pos => $val)
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-map-marker-alt"></i> {{ $pos }}: <strong>{{ $val }}</strong>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="badge {{ $insight->score >= 5 ? 'bg-success' : ($insight->score >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ number_format($insight->score, 1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $insight->formula->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="{{ route('caulo.timeline', $insight->formula_id) }}" 
                                               target="_blank"
                                               class="text-indigo-600 hover:text-indigo-900">
                                                Xem timeline
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button class="btn btn-sm btn-primary show-details-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#detailsModal"
                                                    data-insight="{{ json_encode([
                                                        'type' => $insight->type,
                                                        'hit' => $insight->hit,
                                                        'extra' => $insight->processed_extra
                                                    ]) }}">
                                                Xem chi tiết
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $insights->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Chi tiết</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Trạng thái Hit</h6>
                        <div id="hitStatus" class="mb-4"></div>
                        
                        <h6>Giá trị</h6>
                        <div id="value" class="mb-4"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Dự đoán theo vị trí</h6>
                        <div id="predictedValues" class="mb-4"></div>
                        
                        <h6>Thông tin khác</h6>
                        <div id="otherInfo" class="mb-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    font-size: 0.9em;
    padding: 0.5em 0.8em;
}

.show-details-btn {
    min-width: 100px;
}

.modal-body h6 {
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.modal-body pre {
    background: #f8f9fa;
    padding: 0.5em;
    border-radius: 4px;
    margin: 0;
    font-size: 0.9em;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hiển thị modal chi tiết
    const detailsModal = document.getElementById('detailsModal');
    const hitStatus = document.getElementById('hitStatus');
    const value = document.getElementById('value');
    const predictedValues = document.getElementById('predictedValues');
    const otherInfo = document.getElementById('otherInfo');
    
    document.querySelectorAll('.show-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.insight);
            
            // Hiển thị trạng thái hit
            if (data.hit) {
                hitStatus.innerHTML = `
                    <div class="alert alert-success">
                        <strong>Trúng:</strong> ${JSON.stringify(data.hit, null, 2)}
                    </div>
                `;
            } else {
                hitStatus.innerHTML = `
                    <div class="alert alert-danger">
                        Không trúng
                    </div>
                `;
            }
            
            // Hiển thị giá trị
            value.innerHTML = `
                <pre>${JSON.stringify(data.extra.value, null, 2)}</pre>
            `;
            
            // Hiển thị dự đoán theo vị trí
            if (data.extra.predicted_values_by_position) {
                let html = '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead><tr><th>Vị trí</th><th>Giá trị dự đoán</th></tr></thead><tbody>';
                
                Object.entries(data.extra.predicted_values_by_position).forEach(([pos, val]) => {
                    html += `<tr><td>${pos}</td><td>${val}</td></tr>`;
                });
                
                html += '</tbody></table></div>';
                predictedValues.innerHTML = html;
            } else {
                predictedValues.innerHTML = '<div class="alert alert-info">Không có dữ liệu dự đoán</div>';
            }
            
            // Hiển thị thông tin khác
            const otherData = {...data.extra};
            delete otherData.value;
            delete otherData.predicted_values_by_position;
            delete otherData.positions;
            
            otherInfo.innerHTML = `
                <pre>${JSON.stringify(otherData, null, 2)}</pre>
            `;
        });
    });

    // Hiển thị trạng thái trúng
    const showHitStatusBtn = document.getElementById('showHitStatusBtn');
    let showHitStatus = false;
    showHitStatusBtn.addEventListener('click', function() {
        showHitStatus = !showHitStatus;
        if (showHitStatus) {
            document.querySelectorAll('.hit-status-row').forEach(row => {
                if (!row.classList.contains('hit-null-row') && !row.classList.contains('hit-not-null-row')) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                    if (row.classList.contains('hit-null-row')) {
                        row.style.backgroundColor = '#ffdddd';
                    } else if (row.classList.contains('hit-not-null-row')) {
                        row.style.backgroundColor = '#ddffdd';
                    }
                }
            });
            showHitStatusBtn.textContent = 'Hiển thị tất cả';
        } else {
            document.querySelectorAll('.hit-status-row').forEach(row => {
                row.style.display = '';
                row.style.backgroundColor = '';
            });
            showHitStatusBtn.textContent = 'Hiển thị trạng thái trúng';
        }
    });
});
</script>
@endpush
@endsection 