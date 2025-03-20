
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Tìm Cầu Lô</h2>
    <div class="card">
        <div class="card-body">
            <input type="date" id="searchDate" class="form-control mb-3" value="{{ date('Y-m-d') }}">
            <div id="results" class="list-group">
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchDate').addEventListener('change', function() {
    fetch(`/caulo/search?date=${this.value}`)
        .then(res => res.json())
        .then(data => {
            const results = document.getElementById('results');
            results.innerHTML = data.slice(0, 20).map(hit => `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>Cầu Lô #${hit.cau_lo_id}</div>
                        <a href="/caulo/timeline/${hit.cau_lo_id}?date=${hit.ngay_trung}" 
                           class="btn btn-sm btn-primary">Xem Chi Tiết</a>
                    </div>
                </div>
            `).join('');
        });
});
</script>
@endsection
