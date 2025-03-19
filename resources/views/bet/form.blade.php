
@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Đặt Cược</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="/bet">
            @csrf
            <div class="mb-3">
                <label class="form-label">Số Đánh</label>
                <input type="text" name="lo_number" class="form-control" required maxlength="2">
            </div>
            <div class="mb-3">
                <label class="form-label">Số Tiền</label>
                <input type="number" name="amount" class="form-control" required min="1000">
            </div>
            <button type="submit" class="btn btn-primary">Đặt Cược</button>
        </form>
    </div>
</div>
@endsection
