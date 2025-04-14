
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Heatmap Streak 20 Ngày Gần Nhất</h2>
    
    <div class="heatmap-container d-flex overflow-auto">
        @foreach($timelineData as $date => $streaks)
            <div class="heatmap-day me-3">
                <h6>{{ $date }}</h6>
                <div class="heatmap-grid">
                    @for($i = 0; $i < 100; $i++)
                        @php
                            $streak = 0;
                            if(isset($streaks[$i])) {
                                $streak = count(explode(',', $streaks[$i]->ngay_trung));
                            }
                            $colorClass = match(true) {
                                $streak === 0 => 'bg-dark',
                                $streak === 2 => 'bg-success-light',
                                $streak === 3 => 'bg-success',
                                $streak === 4 => 'bg-success-dark',
                                $streak === 5 => 'bg-success-darker',
                                $streak === 6 => 'bg-warning',
                                default => 'bg-danger'
                            };
                        @endphp
                        <div class="heatmap-cell {{ $colorClass }}" 
                             data-formula-id="{{ $i }}"
                             data-date="{{ $date }}"
                             data-streak="{{ $streak }}">
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.heatmap-container {
    display: flex;
    overflow-x: auto;
    padding: 20px 0;
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
}

.heatmap-cell {
    aspect-ratio: 1;
    border-radius: 2px;
    cursor: pointer;
}

.bg-success-light { background: #90EE90; }
.bg-success-dark { background: #228B22; }
.bg-success-darker { background: #006400; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cells = document.querySelectorAll('.heatmap-cell');
    
    cells.forEach(cell => {
        cell.addEventListener('click', function() {
            const formulaId = this.dataset.formulaId;
            // Highlight các cell cùng formula ID
            cells.forEach(c => {
                if(c.dataset.formulaId === formulaId) {
                    c.style.border = '2px solid #fff';
                } else {
                    c.style.border = 'none';
                }
            });
        });
    });
});
</script>
@endsection
