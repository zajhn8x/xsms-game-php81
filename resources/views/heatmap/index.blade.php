
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-center">Heatmap Streak 20 Ngày Gần Nhất</h2>
            <p class="text-center text-muted">
                Từ {{ $startDate->format('d/m/Y') }} đến {{ $endDate->format('d/m/Y') }}
            </p>
        </div>
    </div>

    {{-- Chú thích màu sắc --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Chú thích màu sắc</h5>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="d-flex align-items-center">
                            <div class="bg-dark border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Không trúng</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success-light border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak 2</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak 3-4</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success-darker border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak 5</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-warning border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak 6</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-danger border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak >6</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Heatmap container --}}
    <div class="row">
        <div class="col-12">
            <div class="heatmap-scroll">
                <div class="heatmap-container d-flex">
                    @foreach($heatmapData as $date => $dayData)
                        <div class="heatmap-day me-3">
                            <h6 class="text-center mb-2">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h6>
                            <div class="heatmap-grid">
                                @foreach($dayData as $cell)
                                    @php
                                        $colorClass = match($cell['streak']) {
                                            0 => 'bg-dark',
                                            2 => 'bg-success-light',
                                            3, 4 => 'bg-success',
                                            5 => 'bg-success-darker',
                                            6 => 'bg-warning',
                                            default => 'bg-danger'
                                        };
                                    @endphp
                                    <div class="heatmap-cell {{ $colorClass }}" 
                                         data-bs-toggle="tooltip"
                                         data-bs-placement="top"
                                         title="ID: {{ $cell['id'] }}, Streak: {{ $cell['streak'] }}"
                                         data-formula-id="{{ $cell['id'] }}"
                                         data-date="{{ $date }}"
                                         data-streak="{{ $cell['streak'] }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.heatmap-scroll {
    overflow-x: auto;
    padding: 20px 0;
}

.heatmap-container {
    min-width: max-content;
}

.heatmap-day {
    min-width: 200px;
}

.heatmap-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 2px;
    background: #eee;
    padding: 2px;
    border-radius: 4px;
}

.heatmap-cell {
    aspect-ratio: 1;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.heatmap-cell:hover {
    transform: scale(1.1);
    z-index: 1;
}

.bg-success-light { background-color: #90EE90 !important; }
.bg-success-darker { background-color: #006400 !important; }

.selected-cell {
    border: 2px solid #fff !important;
    box-shadow: 0 0 0 2px #000;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Xử lý click cell
    const cells = document.querySelectorAll('.heatmap-cell');
    cells.forEach(cell => {
        cell.addEventListener('click', function() {
            const formulaId = this.dataset.formulaId;
            
            // Reset all cells
            cells.forEach(c => c.classList.remove('selected-cell'));
            
            // Highlight cells with same formula ID
            cells.forEach(c => {
                if(c.dataset.formulaId === formulaId) {
                    c.classList.add('selected-cell');
                }
            });
        });
    });
});
</script>
@endpush
@endsection
