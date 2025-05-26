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
                            <span class="ms-2">Streak 3</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success-dark border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">Streak 4</span>
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
                        <div class="d-flex align-items-center ms-auto">
                            <button id="goToTimelineBtn" class="btn btn-primary">
                                Xem Timeline
                            </button>
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
                                @foreach($dayData["data"] as $cell)
                                    @php
                                        $colorClass = match($cell['streak']) {
                                            0 => 'bg-dark',
                                            1 => 'default',
                                            2 => 'bg-success-light',
                                            3 => 'bg-success',
                                            4 => 'bg-success-dark',
                                            5 => 'bg-success-darker',
                                            6 => 'bg-warning',
                                            default => 'bg-danger'
                                        };
                                    @endphp
                                    <div class="heatmap-cell {{ $colorClass }}"
                                         data-bs-toggle="tooltip"
                                         data-bs-placement="top"
                                         title="ID: {{ $cell['id'] }}, Streak: {{ $cell['streak'] }}{{ isset($cell['suggest']) ? ', Suggest: ' . $cell['suggest'] : '' }}"
                                         data-formula-id="{{ $cell['id'] }}"
                                         data-date="{{ $date }}"
                                         data-streak="{{ $cell['streak'] }}">
                                    </div>
                                @endforeach
                            </div>
                            @php
                            /**
                            <ul class="col-12 w-25">
                                <h6>Normal</h6>
                                @foreach($dayData["heads-tails"]["normal"] as $key => $cell)
                                    <li> {{$key . " => " . json_encode($cell)  }}</li>
                                @endforeach
                                <h6>Forward - only</h6>
                                @foreach($dayData["heads-tails"]["forward_only"] as $cell)
                                    <li>{{$key . " => " . json_encode($cell)  }}</li>
                                @endforeach
                            </ul>
                            */
                            @endphp
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-streak-2 { background-color: #C8FACC !important; }  /* Xanh nhạt dễ chịu */
    .bg-streak-3 { background-color: #A3E4A3 !important; }
    .bg-streak-4 { background-color: #6DC96D !important; }
    .bg-streak-5 { background-color: #3DAB3D !important; }  /* Xanh đậm rõ nét */
    .bg-streak-6 { background-color: #FFA726 !important; }  /* Cam tươi */
    .bg-streak-7 { background-color: #FB4C4C !important; }  /* Đỏ nhạt */
    .bg-streak-max { background-color: #B71C1C !important; }/* Đỏ đậm */
    .heatmap-scroll {
        overscroll-behavior-x: contain;

        overflow-x: auto;
    }
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

.bg-success-light {
    background-color: #C3F7C3 !important; /* xanh rất nhạt */
}

.bg-success-dark {
    background-color: #1f8a3a !important; /* xanh đậm hơn, nhưng vẫn khác biệt */
}

.bg-success-darker {
    background-color: #006400 !important; /* xanh rất đậm */
}

.heatmap-cell {
    aspect-ratio: 1;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.heatmap-cell:hover {
    transform: scale(1.1);
    z-index: 1;
}

.selected-cell {
    outline: 2px solid #fff;
    box-shadow: 0 0 0 3px #0d6efd;
    transform: scale(1.15);
    z-index: 2;
}
.heatmap-scroll::-webkit-scrollbar {
    height: 10px;
}

.heatmap-scroll::-webkit-scrollbar-thumb {
    background-color: #bbb;
    border-radius: 4px;
}

.heatmap-scroll::-webkit-scrollbar-track {
    background-color: transparent;
}
.heatmap-scroll {
    overflow-x: scroll; /* luôn hiển thị thanh scroll */
    padding: 20px 0;
    scrollbar-color: #ccc transparent; /* Firefox */
    scrollbar-width: thin;
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

    let selectedFormulaId = null;

    // Xử lý click cell
    const cells = document.querySelectorAll('.heatmap-cell');
    cells.forEach(cell => {
        cell.addEventListener('click', function() {
            selectedFormulaId = this.dataset.formulaId;

            // Reset all cells
            cells.forEach(c => c.classList.remove('selected-cell'));

            // Highlight cells with same formula ID
            cells.forEach(c => {
                if(c.dataset.formulaId === selectedFormulaId) {
                    c.classList.add('selected-cell');
                }
            });
        });
    });

    // Thêm event listener cho nút Xem Timeline
    document.getElementById('goToTimelineBtn').addEventListener('click', function() {
        if(selectedFormulaId) {
            window.open('/caulo/timeline/' + selectedFormulaId, '_blank');
        } else {
            alert('Vui lòng chọn một cầu lô trước khi xem timeline');
        }
    });
});
</script>
@endpush
@endsection
