
@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Thống kê</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Số xuất hiện nhiều nhất</div>
                    <div class="card-body">
                        <canvas id="frequentNumbers"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Thống kê lợi nhuận</div>
                    <div class="card-body">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function loadStatistics() {
    try {
        const response = await fetch('/api/statistics');
        const data = await response.json();
        
        // Render charts using Chart.js
        new Chart(document.getElementById('frequentNumbers'), {
            type: 'bar',
            data: data.frequentNumbers
        });
        
        new Chart(document.getElementById('profitChart'), {
            type: 'line',
            data: data.profitData
        });
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

loadStatistics();
</script>
@endpush
