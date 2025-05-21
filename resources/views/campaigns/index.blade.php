@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>Danh sách chiến dịch</h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tạo chiến dịch mới
                </a>
            </div>
        </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                    <table class="table table-hover">
                                <thead>
                                <tr>
                                <th>ID</th>
                                <th>Tên chiến dịch</th>
                                    <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                    <th>Trạng thái</th>
                                <th>Tổng tiền cược</th>
                                <th>Lợi nhuận</th>
                                <th>Tỷ lệ thắng</th>
                                    <th>Thao tác</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($campaigns as $campaign)
                                    <tr>
                                <td>{{ $campaign->id }}</td>
                                <td>
                                    <a href="{{ route('campaigns.show', $campaign->id) }}">
                                        {{ $campaign->name }}
                                    </a>
                                        </td>
                                <td>{{ $campaign->start_date->format('d/m/Y') }}</td>
                                <td>{{ $campaign->end_date ? $campaign->end_date->format('d/m/Y') : '-' }}</td>
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
                                <td>{{ number_format($campaign->total_bet) }}đ</td>
                                <td class="{{ $campaign->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($campaign->total_profit) }}đ
                                        </td>
                                <td>{{ $campaign->win_rate }}%</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('campaigns.show', $campaign->id) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($campaign->status == 'running')
                                        <button type="button" 
                                                class="btn btn-sm btn-success"
                                                onclick="runCampaign({{ $campaign->id }})">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-warning"
                                                onclick="pauseCampaign({{ $campaign->id }})">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        @endif
                                        @if($campaign->status != 'finished')
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                onclick="finishCampaign({{ $campaign->id }})">
                                            <i class="fas fa-stop"></i>
                                        </button>
                                            @endif
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

    @push('scripts')
    <script>
    function runCampaign(id) {
        if(confirm('Bạn có chắc muốn chạy chiến dịch này?')) {
            axios.post(`/campaigns/${id}/run`)
                .then(response => {
                    window.location.reload();
                })
                .catch(error => {
                    alert('Có lỗi xảy ra!');
                });
        }
    }

    function pauseCampaign(id) {
        if(confirm('Bạn có chắc muốn tạm dừng chiến dịch này?')) {
            axios.post(`/campaigns/${id}/pause`)
                .then(response => {
                    window.location.reload();
                })
                .catch(error => {
                    alert('Có lỗi xảy ra!');
                });
        }
    }

    function finishCampaign(id) {
        if(confirm('Bạn có chắc muốn kết thúc chiến dịch này?')) {
            axios.post(`/campaigns/${id}/finish`)
                .then(response => {
                    window.location.reload();
                })
                .catch(error => {
                    alert('Có lỗi xảy ra!');
                });
        }
    }
    </script>
    @endpush
@endsection
