@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>Chi tiết chiến dịch: {{ $campaign->name }}</h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                @if($campaign->status == 'running' or $campaign->status == 'active')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBetModal">
                    <i class="fas fa-plus"></i> Thêm cược
                </button>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Trạng thái:</strong>
                        @switch($campaign->status)
                            @case('active')
                                <span class="badge bg-success">Sẵn sàng</span>
                                @break
                            @case('waiting')
                                <span class="badge bg-warning">Chờ</span>
                                @break
                            @case('paused')
                                <span class="badge bg-warning">Tạm dừng</span>
                                @break
                            @case('completed')
                                <span class="badge bg-secondary">Đã kết thúc</span>
                                @break
                            @default
                                <span class="badge bg-light">{{ $campaign->status }}</span>
                        @endswitch
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            @if($campaign->status === 'active' || $campaign->status === 'waiting')
                            <button class="btn btn-success btn-sm" onclick="runCampaign({{ $campaign->id }})">
                                <i class="fas fa-play"></i> Chạy
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="pauseCampaign({{ $campaign->id }})">
                                <i class="fas fa-pause"></i> Tạm dừng
                            </button>
                            @endif
                            <button class="btn btn-danger btn-sm" onclick="deleteCampaign({{ $campaign->id }})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Ngày bắt đầu:</strong> {{ $campaign->start_date }}</div>
                    <div class="col-md-4"><strong>Ngày kết thúc:</strong> {{ $campaign->end_date ?? '-' }}</div>
                    <div class="col-md-4"><strong>Số ngày:</strong> {{ $campaign->days }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Số dư ban đầu:</strong> {{ number_format($campaign->initial_balance) }}đ</div>
                    <div class="col-md-4"><strong>Số dư hiện tại:</strong> {{ number_format($campaign->current_balance) }}đ</div>
                    <div class="col-md-4"><strong>Người tạo:</strong> {{ $campaign->user->name ?? '-' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12"><strong>Mô tả:</strong> {{ $campaign->description }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin tổng quan -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin chiến dịch</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    @switch($campaign->status)
                                        @case('running')
                                            <span class="badge bg-success">Đang chạy</span>
                                            @break
                                        @case('paused')
                                            <span class="badge bg-warning">Tạm dừng</span>
                                            @break
                                        @case('finished')
                                            <span class="badge bg-secondary">Đã kết thúc</span>
                                            @break
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <th>Ngày bắt đầu:</th>
                                <td>{{ $campaign->start_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Ngày kết thúc:</th>
                                <td>{{ $campaign->end_date ? $campaign->end_date->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Mô tả:</th>
                                <td>{{ $campaign->description ?: 'Không có' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thống kê</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h6>Tổng tiền cược</h6>
                                <h4 class="text-primary">{{ number_format($campaign->total_bet) }}đ</h4>
                            </div>
                            <div class="col-6 mb-3">
                                <h6>Lợi nhuận</h6>
                                <h4 class="{{ $campaign->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($campaign->total_profit) }}đ
                                </h4>
                            </div>
                            <div class="col-6">
                                <h6>Số lần cược</h6>
                                <h4>{{ $campaign->total_bet_count }}</h4>
                            </div>
                            <div class="col-6">
                                <h6>Tỷ lệ thắng</h6>
                                <h4>{{ $campaign->win_rate }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách cược -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lịch sử cược</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Số lô</th>
                                        <th>Tiền cược</th>
                                        <th>Kết quả</th>
                                        <th>Lợi nhuận</th>
                                        <th>Phân tích</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bets as $bet)
                                    <tr>
                                        <td>{{ $bet->bet_date->format('d/m/Y') }}</td>
                                        <td>
                                            @foreach(json_decode($bet->bet_numbers) as $number)
                                                <span class="badge bg-info">{{ $number }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ number_format($bet->bet_amount) }}đ</td>
                                        <td>
                                            @if($bet->result > 0)
                                                <span class="badge bg-success">Trúng {{ $bet->result }} lần</span>
                                            @else
                                                <span class="badge bg-danger">Thua</span>
                                            @endif
                                        </td>
                                        <td class="{{ $bet->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($bet->profit) }}đ
                                        </td>
                                        <td>
                                            <button class="btn btn-link btn-sm" data-bs-toggle="modal" data-bs-target="#betDetailModal{{ $bet->id }}">
                                                <i class="fas fa-search"></i> Phân tích
                                            </button>
                                            <!-- Modal phân tích bet -->
                                            <div class="modal fade" id="betDetailModal{{ $bet->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Phân tích chi tiết cược ngày {{ $bet->bet_date->format('d/m/Y') }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Số lô đã cược:</strong>
                                                                @foreach(json_decode($bet->bet_numbers) as $number)
                                                                    <span class="badge bg-info">{{ $number }}</span>
                                                                @endforeach
                                                            </p>
                                                            <p><strong>Kết quả:</strong>
                                                                @if($bet->result > 0)
                                                                    Trúng {{ $bet->result }} lần
                                                                @else
                                                                    Thua
                                                                @endif
                                                            </p>
                                                            <p><strong>Lợi nhuận:</strong> {{ number_format($bet->profit) }}đ</p>
                                                            <hr>
                                                            <h6>Phân tích hiệu quả:</h6>
                                                            <ul>
                                                                <li>Số lô trúng: <b>{{ $bet->result }}</b></li>
                                                                <li>Tỷ lệ trúng: <b>{{ $bet->result > 0 ? round($bet->result/count(json_decode($bet->bet_numbers))*100,2) : 0 }}%</b></li>
                                                                <li>Số lô không trúng: <b>{{ $bet->result > 0 ? count(json_decode($bet->bet_numbers)) - $bet->result : count(json_decode($bet->bet_numbers)) }}</b></li>
                                                            </ul>
                                                            @if($bet->insight)
                                                            <hr>
                                                            <h6>Insight liên quan:</h6>
                                                            <pre class="bg-light p-2">{{ json_encode($bet->insight, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm cược -->
    <div class="modal fade" id="addBetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('campaigns.bets.store', $campaign->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm cược mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ngày đặt cược</label>
                            <input type="date" name="bet_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số lô (cách nhau bằng dấu phẩy)</label>
                            <input type="text" name="bet_numbers" class="form-control"
                                   placeholder="VD: 18,25,36" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số tiền cược</label>
                            <input type="number" name="bet_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm cược</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function runCampaign(id) {
    if(confirm('Bạn có chắc muốn chạy chiến dịch này?')) {
        axios.post(`/campaigns/${id}/run`)
            .then(response => { window.location.reload(); })
            .catch(error => { alert('Có lỗi xảy ra!'); });
    }
}
function pauseCampaign(id) {
    if(confirm('Bạn có chắc muốn tạm dừng chiến dịch này?')) {
        axios.post(`/campaigns/${id}/pause`)
            .then(response => { window.location.reload(); })
            .catch(error => { alert('Có lỗi xảy ra!'); });
    }
}
function deleteCampaign(id) {
    if(confirm('Bạn có chắc muốn xóa chiến dịch này?')) {
        axios.post(`/campaigns/${id}`, {_method: 'DELETE'})
            .then(response => { window.location.href = '/campaigns'; })
            .catch(error => { alert('Có lỗi xảy ra!'); });
    }
}
</script>
@endpush
