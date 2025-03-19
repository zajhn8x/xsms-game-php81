
@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Đặt cược</h2>
    </div>
    <div class="card-body">
        <form id="bet-form" method="POST" action="/api/bets">
            @csrf
            <div class="mb-3">
                <label class="form-label">Số đánh:</label>
                <input type="text" class="form-control" name="number" maxlength="2" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Số tiền:</label>
                <input type="number" class="form-control" name="amount" min="1000" step="1000" required>
            </div>
            <button type="submit" class="btn btn-primary">Đặt cược</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('bet-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('/api/bets', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        e.target.reset();
    } catch (error) {
        console.error('Error placing bet:', error);
        alert('Có lỗi xảy ra khi đặt cược');
    }
});
</script>
@endpush
