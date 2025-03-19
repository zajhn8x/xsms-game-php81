
@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Kết quả xổ số</h2>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col">
                <input type="date" class="form-control" id="date-picker">
            </div>
            <div class="col">
                <button class="btn btn-primary" id="load-results">Xem kết quả</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Giải</th>
                        <th>Số trúng</th>
                    </tr>
                </thead>
                <tbody id="results-body">
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('load-results').addEventListener('click', async () => {
    const date = document.getElementById('date-picker').value;
    try {
        const response = await fetch(`/api/lottery-results/${date}`);
        const data = await response.json();
        // Render results
        const tbody = document.getElementById('results-body');
        tbody.innerHTML = Object.entries(data.prizes)
            .map(([prize, number]) => `
                <tr>
                    <td>${prize}</td>
                    <td>${number}</td>
                </tr>
            `).join('');
    } catch (error) {
        console.error('Error loading results:', error);
    }
});
</script>
@endpush
