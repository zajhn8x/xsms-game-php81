@extends('layouts.app')

@section('title', 'Quản lý Sub-Campaigns')

@section('content')
{{-- Micro-task 2.1.4.5: Sub-campaign UI components (5h) --}}
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Sub-Campaigns</h2>
                    <p class="text-muted">{{ $campaign->name }}</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubCampaignModal">
                        <i class="fas fa-plus"></i> Tạo Sub-Campaign
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#splitCampaignModal">
                        <i class="fas fa-cut"></i> Chia Campaign
                    </button>
                </div>
            </div>

            {{-- Campaign Overview Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-wallet fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Số dư chính</h6>
                                    <h4 class="mb-0 text-primary">{{ number_format($campaign->current_balance) }} ₫</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-line fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Số dư phân bổ</h6>
                                    <h4 class="mb-0 text-success">{{ number_format($aggregation['summary']['total_allocated_balance']) }} ₫</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-tasks fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Sub-Campaigns</h6>
                                    <h4 class="mb-0 text-info">{{ $aggregation['summary']['total_sub_campaigns'] }}</h4>
                                    <small class="text-muted">{{ $aggregation['summary']['active_sub_campaigns'] }} đang hoạt động</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-percentage fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">ROI trung bình</h6>
                                    <h4 class="mb-0 text-warning">{{ number_format($aggregation['summary']['average_roi'], 1) }}%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sub-Campaigns Table --}}
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="subCampaignTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                Tất cả ({{ $subCampaigns->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                                Đang hoạt động ({{ $subCampaigns->where('status', 'active')->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                Chờ khởi động ({{ $subCampaigns->where('status', 'pending')->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
                                Hoàn thành ({{ $subCampaigns->where('status', 'completed')->count() }})
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="subCampaignTabsContent">
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            @include('campaigns.partials.sub-campaign-table', ['subCampaigns' => $subCampaigns])
                        </div>
                        <div class="tab-pane fade" id="active" role="tabpanel">
                            @include('campaigns.partials.sub-campaign-table', ['subCampaigns' => $subCampaigns->where('status', 'active')])
                        </div>
                        <div class="tab-pane fade" id="pending" role="tabpanel">
                            @include('campaigns.partials.sub-campaign-table', ['subCampaigns' => $subCampaigns->where('status', 'pending')])
                        </div>
                        <div class="tab-pane fade" id="completed" role="tabpanel">
                            @include('campaigns.partials.sub-campaign-table', ['subCampaigns' => $subCampaigns->where('status', 'completed')])
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Analytics --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Phân tích theo loại Sub-Campaign</h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($aggregation['by_type']))
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Loại</th>
                                                <th>Số lượng</th>
                                                <th>Số dư</th>
                                                <th>ROI TB</th>
                                                <th>Tỷ lệ thắng</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($aggregation['by_type'] as $type => $data)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-{{ $type === 'test' ? 'warning' : ($type === 'split' ? 'info' : 'secondary') }}">
                                                        {{ ucfirst($type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $data['count'] }}</td>
                                                <td>{{ number_format($data['total_current']) }} ₫</td>
                                                <td>
                                                    <span class="text-{{ $data['avg_roi'] >= 0 ? 'success' : 'danger' }}">
                                                        {{ number_format($data['avg_roi'], 1) }}%
                                                    </span>
                                                </td>
                                                <td>{{ number_format($data['avg_win_rate'], 1) }}%</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center">Chưa có dữ liệu phân tích</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Top Performers</h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($aggregation['performance']['risk_adjusted_performance']['rankings']))
                                <div class="list-group list-group-flush">
                                    @foreach(array_slice($aggregation['performance']['risk_adjusted_performance']['rankings'], 0, 5) as $index => $performer)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <span class="badge badge-primary me-2">{{ $index + 1 }}</span>
                                            <strong>{{ $performer['name'] }}</strong>
                                            <small class="text-muted d-block">ROI: {{ number_format($performer['roi'], 1) }}%</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge badge-success">{{ number_format($performer['risk_adjusted_score'], 2) }}</span>
                                            <small class="text-muted d-block">Risk Score</small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">Chưa có dữ liệu performance</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Sub-Campaign Modal --}}
<div class="modal fade" id="createSubCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createSubCampaignForm" method="POST" action="{{ route('campaigns.sub-campaigns.store', $campaign) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tạo Sub-Campaign Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_name" class="form-label">Tên Sub-Campaign</label>
                                <input type="text" class="form-control" id="sub_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_type" class="form-label">Loại</label>
                                <select class="form-select" id="sub_type" name="type" required>
                                    <option value="segment">Segment</option>
                                    <option value="test">Test</option>
                                    <option value="backup">Backup</option>
                                    <option value="split">Split</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sub_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="sub_description" name="description" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_allocated_balance" class="form-label">Số dư phân bổ</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="sub_allocated_balance" name="allocated_balance" min="10000" max="{{ $campaign->current_balance }}" required>
                                    <span class="input-group-text">₫</span>
                                </div>
                                <small class="form-text text-muted">Tối đa: {{ number_format($campaign->current_balance) }} ₫</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_priority" class="form-label">Độ ưu tiên</label>
                                <select class="form-select" id="sub_priority" name="priority">
                                    <option value="1">Cao nhất (1)</option>
                                    <option value="2">Cao (2)</option>
                                    <option value="3" selected>Trung bình (3)</option>
                                    <option value="4">Thấp (4)</option>
                                    <option value="5">Thấp nhất (5)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_betting_strategy" class="form-label">Chiến lược đặt cược</label>
                                <select class="form-select" id="sub_betting_strategy" name="betting_strategy" required>
                                    <option value="manual">Thủ công</option>
                                    <option value="auto_heatmap">Auto Heatmap</option>
                                    <option value="auto_streak">Auto Streak</option>
                                    <option value="auto_pattern">Auto Pattern</option>
                                    <option value="auto_hybrid">Auto Hybrid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sub_auto_start" name="auto_start">
                                    <label class="form-check-label" for="sub_auto_start">
                                        Tự động khởi động
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sub_auto_stop" name="auto_stop" checked>
                                    <label class="form-check-label" for="sub_auto_stop">
                                        Tự động dừng
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_start_date" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="sub_start_date" name="start_date" value="{{ $campaign->start_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sub_end_date" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="sub_end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo Sub-Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Split Campaign Modal --}}
<div class="modal fade" id="splitCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="splitCampaignForm" method="POST" action="{{ route('campaigns.sub-campaigns.split', $campaign) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Chia Campaign thành Sub-Campaigns</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số dư khả dụng: <strong>{{ number_format($campaign->current_balance) }} ₫</strong></label>
                    </div>

                    <div id="splitItems">
                        <div class="split-item border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="splits[0][name]" placeholder="Tên sub-campaign" required>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="number" class="form-control percentage-input" name="splits[0][percentage]" min="1" max="100" placeholder="%" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control amount-display" readonly placeholder="Số tiền">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-split" disabled>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="split-item border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="splits[1][name]" placeholder="Tên sub-campaign" required>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="number" class="form-control percentage-input" name="splits[1][percentage]" min="1" max="100" placeholder="%" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control amount-display" readonly placeholder="Số tiền">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-split">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addSplitItem">
                            <i class="fas fa-plus"></i> Thêm sub-campaign
                        </button>
                        <div>
                            <span class="text-muted">Tổng: </span>
                            <span id="totalPercentage" class="fw-bold">0%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="splitSubmitBtn" disabled>Chia Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableBalance = {{ $campaign->current_balance }};
    let splitItemIndex = 2;

    // Calculate split amounts
    function calculateSplitAmounts() {
        const percentageInputs = document.querySelectorAll('.percentage-input');
        const amountDisplays = document.querySelectorAll('.amount-display');
        let totalPercentage = 0;

        percentageInputs.forEach((input, index) => {
            const percentage = parseFloat(input.value) || 0;
            totalPercentage += percentage;

            const amount = (percentage / 100) * availableBalance;
            if (amountDisplays[index]) {
                amountDisplays[index].value = new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + ' ₫';
            }
        });

        document.getElementById('totalPercentage').textContent = totalPercentage + '%';
        document.getElementById('totalPercentage').className = totalPercentage === 100 ? 'fw-bold text-success' : (totalPercentage > 100 ? 'fw-bold text-danger' : 'fw-bold');

        document.getElementById('splitSubmitBtn').disabled = totalPercentage !== 100;
    }

    // Add split item
    document.getElementById('addSplitItem').addEventListener('click', function() {
        const splitItems = document.getElementById('splitItems');
        const newItem = document.createElement('div');
        newItem.className = 'split-item border rounded p-3 mb-3';
        newItem.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="splits[${splitItemIndex}][name]" placeholder="Tên sub-campaign" required>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="number" class="form-control percentage-input" name="splits[${splitItemIndex}][percentage]" min="1" max="100" placeholder="%" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control amount-display" readonly placeholder="Số tiền">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-split">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        splitItems.appendChild(newItem);
        splitItemIndex++;

        // Add event listeners to new inputs
        newItem.querySelector('.percentage-input').addEventListener('input', calculateSplitAmounts);
        newItem.querySelector('.remove-split').addEventListener('click', function() {
            newItem.remove();
            calculateSplitAmounts();
            updateRemoveButtons();
        });

        updateRemoveButtons();
    });

    // Remove split item
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-split');
        removeButtons.forEach(button => {
            button.disabled = removeButtons.length <= 2;
        });
    }

    // Event listeners
    document.querySelectorAll('.percentage-input').forEach(input => {
        input.addEventListener('input', calculateSplitAmounts);
    });

    document.querySelectorAll('.remove-split').forEach(button => {
        button.addEventListener('click', function() {
            button.closest('.split-item').remove();
            calculateSplitAmounts();
            updateRemoveButtons();
        });
    });

    updateRemoveButtons();
});
</script>
@endpush
@endsection
