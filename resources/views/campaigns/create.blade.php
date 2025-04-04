
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Tạo chiến dịch mới</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('campaigns.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số ngày chơi</label>
                            <input type="number" name="days" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vốn ban đầu</label>
                            <input type="number" name="initial_balance" class="form-control" min="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phương thức cược</label>
                            <select name="bet_type" class="form-control" required>
                                <option value="manual">Thủ công</option>
                                <option value="formula">Theo công thức</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Tạo chiến dịch</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
