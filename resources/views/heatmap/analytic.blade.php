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
                                <a href="{{ route('heatmap.analytic', $prevDate->format('Y-m-d')) }}"
                                    class="btn btn-outline-primary">
                                    <i class="fas fa-chevron-left"></i> Ngày trước
                                </a>
                            </div>
                            <div class="flex-grow-1 mx-3">
                                <select class="form-select"
                                    onchange="window.location.href = '{{ route('heatmap.analytic', '') }}/' + this.value">
                                    @foreach ($availableDates as $date)
                                        <option value="{{ $date }}"
                                            {{ $date === $currentDate->format('Y-m-d') ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <a href="{{ route('heatmap.analytic', $nextDate->format('Y-m-d')) }}"
                                    class="btn btn-outline-primary">
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
                            <div class="col-md-6">
                                <label class="form-label">Loại</label>
                                <select name="type" class="form-select">
                                    <option value="">Tất cả</option>
                                    @foreach ($types as $value => $name)
                                        <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Lọc</button>
                                    <a href="{{ route('heatmap.analytic', $currentDate->format('Y-m-d')) }}"
                                        class="btn btn-secondary">Reset</a>
                                    <button type="button" class="btn btn-info" id="showHitStatusBtn">Hiển thị trạng thái
                                        trúng</button>
                                </div>
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
                                        <th>STT</th>
                                        <th>ID</th>
                                        <th>Loại</th>
                                        <th>Streak</th>
                                        <th>Trạng thái</th>
                                        <th>Điểm</th>
                                        <th>Công thức</th>
                                        <th>Timeline</th>
                                        <th>Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($insights as $index => $insight)
                                        <tr
                                            class="
                                        {{ $insight->type === 'long_run' ? 'table-warning' : ($insight->type === 'long_run_stop' ? 'table-danger' : 'table-success') }}
                                        hit-status-row
                                        {{ $insight->hit === null ? 'hit-null-row' : ($insight->hit ? 'hit-true-row' : 'hit-false-row') }}
                                    ">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $insight->formula_id }}</td>
                                            <td>
                                                @php
                                                    $typeClasses = [
                                                        'long_run' => 'bg-warning',
                                                        'long_run_stop' => 'bg-danger',
                                                        'rebound_after_long_run' => 'bg-success'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $typeClasses[$insight->type] ?? 'bg-secondary' }}">
                                                    {{ $types[$insight->type] ?? $insight->type }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $streak = $insight->processed_extra['streak_length'] ?? null;
                                                @endphp
                                                @if ($streak !== null)
                                                    @if ($streak >= 6)
                                                        <span class="badge bg-success">{{ $streak }}</span>
                                                    @elseif($streak >= 4)
                                                        <span class="badge bg-warning text-dark">{{ $streak }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $streak }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $extra = $insight->extra ?? [];

                                                    // Xác định màu cho running
                                                    $runningClass = 'bg-info';
                                                    if (isset($extra['running'])) {
                                                        switch($extra['running']) {
                                                            case 'step_1':
                                                                $runningClass = 'bg-warning text-dark';
                                                                break;
                                                            case 'step_2':
                                                                $runningClass = 'bg-info';
                                                                break;
                                                            case 'step_3':
                                                                $runningClass = 'bg-success';
                                                                break;
                                                        }
                                                    }

                                                    // Xác định màu cho stop_days
                                                    $stopDaysClass = 'bg-warning text-dark';
                                                    if (isset($extra['stop_days'])) {
                                                        if ($extra['stop_days'] >= 3) {
                                                            $stopDaysClass = 'bg-danger';
                                                        } else if ($extra['stop_days'] == 2) {
                                                            $stopDaysClass = 'bg-warning text-dark';
                                                        } else {
                                                            $stopDaysClass = 'bg-info';
                                                        }
                                                    }

                                                    $mainInfo = [
                                                        'running' => ['label' => 'Running', 'class' => $runningClass],
                                                        'suggests' => ['label' => 'Suggests', 'class' => 'bg-success'],
                                                        'stop_days' => ['label' => 'Stop', 'class' => $stopDaysClass],
                                                        'streak_length' => ['label' => 'Streak', 'class' => 'bg-primary']
                                                    ];
                                                    $stepInfo = ['step_1', 'step_2', 'step_3'];
                                                @endphp
                                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                                    {{-- Hiển thị thông tin chính --}}
                                                    @foreach($mainInfo as $key => $config)
                                                        @if(isset($extra[$key]))
                                                            <span class="badge {{ $config['class'] }}">
                                                                {{ $config['label'] }}:
                                                                @if($key === 'suggests' && is_array($extra[$key]))
                                                                    {{ implode(', ', $extra[$key]) }}
                                                                @else
                                                                    {{ $extra[$key] }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    @endforeach

                                                    {{-- Hiển thị thông tin step --}}
                                                    @foreach($stepInfo as $step)
                                                        @if(isset($extra[$step]))
                                                            <span class="badge bg-secondary">
                                                                 {{$step. ":" . $extra[$step] }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ $insight->score >= 5 ? 'bg-success' : ($insight->score >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ number_format($insight->score, 1) }}
                                                </span>
                                            </td>
                                            <td>{{ $insight->formula->name }}</td>
                                            <td>
                                                <a href="{{ $insight->link }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    Xem timeline
                                                </a>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary show-details-btn"
                                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                    data-insight="{{ json_encode([
                                                        'type' => $insight->type,
                                                        'hit' => $insight->hit,
                                                        'extra' => $insight->processed_extra,
                                                        'formula_id' => $insight->formula_id,
                                                        'score' => $insight->score
                                                    ]) }}">
                                                    Chi tiết
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($insights->isEmpty())
                            <div class="text-center py-4">
                                <p class="text-muted">Không có dữ liệu cho ngày này</p>
                            </div>
                        @endif
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
                    <h5 class="modal-title" id="detailsModalLabel">Chi tiết Insight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">
                        <!-- Nội dung sẽ được load bằng JavaScript -->
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
        .hit-true-row {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }
        .hit-false-row {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .hit-null-row {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const detailsModal = document.getElementById('detailsModal');
                const showHitStatusBtn = document.getElementById('showHitStatusBtn');
                let showHitStatus = false;

                // Xử lý nút hiển thị trạng thái trúng
                showHitStatusBtn.addEventListener('click', function() {
                    showHitStatus = !showHitStatus;
                    document.querySelectorAll('.hit-status-row').forEach(row => {
                        if (showHitStatus) {
                            // Chỉ hiển thị các row có hit status
                            if (row.classList.contains('hit-null-row')) {
                                row.style.display = 'none';
                            } else {
                                row.style.display = '';
                            }
                        } else {
                            // Hiển thị tất cả
                            row.style.display = '';
                        }
                    });
                    this.textContent = showHitStatus ? 'Hiển thị tất cả' : 'Hiển thị trạng thái trúng';
                });

                // Xử lý modal chi tiết
                document.querySelectorAll('.show-details-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const insightData = JSON.parse(this.getAttribute('data-insight'));
                        const modalContent = document.getElementById('modalContent');

                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Thông tin cơ bản</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Formula ID:</strong></td><td>${insightData.formula_id}</td></tr>
                                        <tr><td><strong>Loại:</strong></td><td>${insightData.type}</td></tr>
                                        <tr><td><strong>Điểm:</strong></td><td>${insightData.score}</td></tr>
                                        <tr><td><strong>Hit:</strong></td><td>
                                            ${insightData.hit === null ? 'Chưa có' : (insightData.hit ? 'Trúng' : 'Không trúng')}
                                        </td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Chi tiết</h6>
                                    <table class="table table-sm">
                        `;

                        if (insightData.extra) {
                            Object.entries(insightData.extra).forEach(([key, value]) => {
                                if (key !== 'predicted_values_by_position') {
                                    html += `<tr><td><strong>${key}:</strong></td><td>${value}</td></tr>`;
                                }
                            });
                        }

                        html += `
                                    </table>
                                </div>
                            </div>
                        `;

                        if (insightData.extra && insightData.extra.predicted_values_by_position) {
                            html += `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Predicted Values by Position</h6>
                                        <div class="d-flex flex-wrap gap-2">
                            `;
                            Object.entries(insightData.extra.predicted_values_by_position).forEach(([pos, val]) => {
                                html += `<span class="badge bg-info">${pos}: ${val}</span>`;
                            });
                            html += `
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        modalContent.innerHTML = html;
                    });
                });
            });
        </script>
    @endpush
@endsection
