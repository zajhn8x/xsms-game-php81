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
                                        <option value="{{ is_array($date) ? $date['value'] : $date }}"
                                            {{ (is_array($date) ? $date['value'] : $date->format('Y-m-d')) === $currentDate->format('Y-m-d') ? 'selected' : '' }}>
                                            {{ is_array($date) ? $date['label'] : $date->format('d/m/Y') }}
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
                            <div class="col-md-4">
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

                            {{-- Score Filter --}}
                            <div class="col-md-3">
                                <label class="form-label">Điểm tối thiểu</label>
                                <input type="number" name="min_score" class="form-control" step="0.1"
                                    value="{{ request('min_score') }}">
                            </div>

                            {{-- Streak Length Filter --}}
                            <div class="col-md-3">
                                <label class="form-label">Streak tối thiểu</label>
                                <input type="number" name="min_streak" class="form-control"
                                    value="{{ request('min_streak') }}">
                            </div>

                            {{-- Hit Status Filter --}}
                            <div class="col-md-2">
                                <label class="form-label">Trạng thái</label>
                                <select name="hit_status" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="true" {{ request('hit_status') === 'true' ? 'selected' : '' }}>Trúng
                                    </option>
                                    <option value="false" {{ request('hit_status') === 'false' ? 'selected' : '' }}>Không
                                        trúng</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Lọc</button>
                                <a href="{{ route('heatmap.analytic', $currentDate->format('Y-m-d')) }}"
                                    class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-info ms-2" id="showHitStatusBtn">Hiển thị trạng thái
                                    trúng</button>
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
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            STT</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Loại</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Streak</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trạng thái</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Điểm</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Công thức</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Timeline</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($insights as $index => $insight)
                                        <tr
                                            class="
                                        {{ $insight->type === 'long_run' ? 'table-warning' : ($insight->type === 'long_run_stop' ? 'table-danger' : 'table-success') }}
                                        hit-status-row
                                        {{ $insight->hit === null ? 'hit-null-row' : 'hit-not-null-row' }}
                                    ">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $insights->firstItem() + $index }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $insight->formula_id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @php

                                                    $typeClasses = [
                                                        'long_run' => 'bg-warning',
                                                        'long_run_stop' => 'bg-danger',
                                                        'rebound_after_long_run' => 'bg-success'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $typeClasses[$insight->type] }}">
                                                    {{ $types[$insight->type] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @php
                                                    $streak = isset($insight->processed_extra)
                                                        ? $insight->processed_extra['streak_length'] ?? null
                                                        : null;
                                                @endphp
                                                @if ($streak !== null)
                                                    @if ($streak >= 6)
                                                        <span class="badge bg-success">Streak: {{ $streak }}</span>
                                                    @elseif($streak == 4 || $streak == 5)
                                                        <span class="badge bg-warning text-dark">Streak:
                                                            {{ $streak }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">Streak: {{ $streak }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="badge {{ $insight->score >= 5 ? 'bg-success' : ($insight->score >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ number_format($insight->score, 1) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $insight->formula->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <a href="{{ route('caulo.timeline', $insight->formula_id) }}"
                                                    target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                                    Xem timeline
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="btn btn-sm btn-primary show-details-btn"
                                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                    data-insight="{{ json_encode([
                                                        'type' => $insight->type,
                                                        'hit' => $insight->hit,
                                                        'extra' => $insight->processed_extra,
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
                        <div class="col-12">
                            @php
                                $infoGroups = [
                                    'main' => [
                                        'title' => 'Thông tin chính',
                                        'class' => 'text-primary',
                                        'fields' => ['running', 'suggests', 'stop_days']
                                    ],
                                    'steps' => [
                                        'title' => 'Thông tin step',
                                        'class' => 'text-secondary',
                                        'fields' => ['step_1', 'step_2', 'step_3']
                                    ],
                                    'other' => [
                                        'title' => 'Thông tin khác',
                                        'class' => 'text-muted',
                                        'fields' => []
                                    ]
                                ];
                            @endphp

                            @foreach($infoGroups as $group)
                                <div class="mb-3">
                                    <h6 class="{{ $group['class'] }}">{{ $group['title'] }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                @foreach($extra as $key => $val)
                                                    @if(in_array($key, $group['fields']) || ($group['title'] === 'Thông tin khác' && !in_array($key, array_merge($infoGroups['main']['fields'], $infoGroups['steps']['fields']))))
                                                        <tr>
                                                            <td class="fw-bold">{{ ucfirst($key) }}</td>
                                                            <td>
                                                                @if(is_array($val))
                                                                    {{ implode(', ', $val) }}
                                                                @else
                                                                    {{ $val }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
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
        .modal-body h6 {
            margin-bottom: 0.5rem;
        }
        .table-sm td {
            padding: 0.5rem;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Xử lý hiển thị modal chi tiết
                const detailsModal = document.getElementById('detailsModal');
                const showHitStatusBtn = document.getElementById('showHitStatusBtn');
                let showHitStatus = false;

                // Xử lý nút hiển thị trạng thái trúng
                showHitStatusBtn.addEventListener('click', function() {
                    showHitStatus = !showHitStatus;
                    document.querySelectorAll('.hit-status-row').forEach(row => {
                        if (showHitStatus) {
                            if (!row.classList.contains('hit-null-row') && !row.classList.contains('hit-not-null-row')) {
                                row.style.display = 'none';
                            } else {
                                row.style.display = '';
                                row.style.backgroundColor = row.classList.contains('hit-null-row') ? '#ffdddd' : '#ddffdd';
                            }
                        } else {
                            row.style.display = '';
                            row.style.backgroundColor = '';
                        }
                    });
                    this.textContent = showHitStatus ? 'Hiển thị tất cả' : 'Hiển thị trạng thái trúng';
                });
            });
        </script>
    @endpush
@endsection
