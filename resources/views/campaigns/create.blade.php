@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>Tạo chiến dịch mới</h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
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
                                <label class="form-label">Tên chiến dịch</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                          rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày bắt đầu</label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                       value="{{ old('start_date', date('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày kết thúc</label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                       value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số ngày</label>
                                <input type="number" id="days" class="form-control" value="30" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Loại chiến dịch (bet_type)</label>
                                <select name="bet_type" class="form-select">
                                    <option value="manual">Thủ công</option>
                                    <option value="auto_heatmap">Tự động Heatmap</option>
                                    <option value="auto_streak">Tự động Top Streak</option>
                                    <option value="auto_rebound">Tự động Rebound</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái khởi tạo</label>
                                <select name="status" class="form-select">
                                    <option value="active">Đang chạy</option>
                                    <option value="paused">Tạm dừng</option>
                                    <option value="finished">Kết thúc</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cách chơi</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="play_type" 
                                           id="play_type_manual" value="manual" checked>
                                    <label class="form-check-label" for="play_type_manual">
                                        Chơi thủ công
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="play_type" 
                                           id="play_type_auto" value="auto">
                                    <label class="form-check-label" for="play_type_auto">
                                        Chơi tự động
                                    </label>
                                </div>
                            </div>

                            <div id="auto_play_options" class="mb-3" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Cấu hình chơi tự động</h6>
                                        
                                        <div class="mb-3">
                                            <label for="insight_type" class="form-label">Loại insight</label>
                                            <select class="form-select" id="insight_type" name="insight_type">
                                                <option value="long_run_stop">Long Run Stop</option>
                                                <option value="rebound_after_long_run">Rebound After Long Run</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="day_stop" class="form-label">Ngày dừng</label>
                                            <select class="form-select" id="day_stop" name="day_stop">
                                                <option value="1">Ngày 1</option>
                                                <option value="2" selected>Ngày 2</option>
                                                <option value="3">Ngày 3</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="bet_amount" class="form-label">Số tiền cược mỗi lần</label>
                                            <input type="number" class="form-control" id="bet_amount" 
                                                   name="bet_amount" value="10000" min="1000" step="1000">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Tạo chiến dịch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const playTypeInputs = document.querySelectorAll('input[name="play_type"]');
        const autoPlayOptions = document.getElementById('auto_play_options');
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        const daysInput = document.getElementById('days');

        playTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'auto') {
                    autoPlayOptions.style.display = 'block';
                } else {
                    autoPlayOptions.style.display = 'none';
                }
            });
        });

        function updateDays() {
            const start = startDateInput.value;
            const end = endDateInput.value;
            if (start && end) {
                const startDate = new Date(start);
                const endDate = new Date(end);
                const diff = Math.floor((endDate - startDate) / (1000*60*60*24)) + 1;
                daysInput.value = diff > 0 ? diff : 1;
            } else {
                daysInput.value = 30;
            }
        }
        startDateInput.addEventListener('change', updateDays);
        endDateInput.addEventListener('change', updateDays);
    });
    </script>
    @endpush
@endsection
