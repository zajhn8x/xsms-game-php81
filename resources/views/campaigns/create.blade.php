
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tạo chiến dịch mới</h5>
                    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Quay lại</a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('campaigns.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Ngày bắt đầu</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>

                        <div class="mb-3">
                            <label for="days" class="form-label">Số ngày</label>
                            <input type="number" class="form-control" id="days" name="days" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="initial_balance" class="form-label">Vốn ban đầu</label>
                            <input type="number" class="form-control" id="initial_balance" name="initial_balance" min="1000" required>
                        </div>

                        <div class="mb-3">
                            <label for="bet_type" class="form-label">Loại cược</label>
                            <select class="form-control" id="bet_type" name="bet_type" required>
                                <option value="manual">Thủ công</option>
                                <option value="formula">Theo công thức</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Tạo chiến dịch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
