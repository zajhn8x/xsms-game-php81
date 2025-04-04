@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Đặt cược cho chiến dịch</h5>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-secondary">Quay lại</a>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('campaigns.bet', $campaign) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="bet_date" class="form-label">Ngày cược</label>
                                <input type="date" class="form-control" id="bet_date" name="bet_date" required>
                            </div>

                            <div class="mb-3">
                                <label for="lo_number" class="form-label">Số lô</label>
                                <input type="number" class="form-control" id="lo_number" name="lo_number" min="0"
                                       max="99" required>
                            </div>

                            <div class="mb-3">
                                <label for="points" class="form-label">Điểm</label>
                                <input type="number" class="form-control" id="points" name="points" min="1" required>
                                <div class="form-text">1 điểm = 23,000 VNĐ</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đặt cược</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
