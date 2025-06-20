{{-- Sub-Campaign Table Partial --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Tên & Loại</th>
                <th>Trạng thái</th>
                <th>Số dư</th>
                <th>Performance</th>
                <th>Chiến lược</th>
                <th>Thời gian</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subCampaigns as $subCampaign)
            <tr>
                <td>
                    <div>
                        <strong>{{ $subCampaign->name }}</strong>
                        <div>
                            <span class="badge badge-{{ $subCampaign->type === 'test' ? 'warning' : ($subCampaign->type === 'split' ? 'info' : 'secondary') }} badge-sm">
                                {{ ucfirst($subCampaign->type) }}
                            </span>
                            @if($subCampaign->priority <= 2)
                                <span class="badge badge-danger badge-sm">Ưu tiên {{ $subCampaign->priority }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-{{
                        $subCampaign->status === 'active' ? 'success' :
                        ($subCampaign->status === 'pending' ? 'warning' :
                        ($subCampaign->status === 'completed' ? 'info' : 'secondary'))
                    }}">
                        {{ ucfirst($subCampaign->status) }}
                    </span>
                    @if($subCampaign->auto_start && $subCampaign->status === 'pending')
                        <small class="d-block text-muted">
                            <i class="fas fa-clock"></i> Auto-start
                        </small>
                    @endif
                </td>
                <td>
                    <div>
                        <strong>{{ number_format($subCampaign->current_balance) }} ₫</strong>
                        <small class="d-block text-muted">
                            Phân bổ: {{ number_format($subCampaign->allocated_balance) }} ₫
                        </small>
                        @php
                            $balanceChange = $subCampaign->current_balance - $subCampaign->allocated_balance;
                            $changePercent = $subCampaign->allocated_balance > 0 ? ($balanceChange / $subCampaign->allocated_balance) * 100 : 0;
                        @endphp
                        <small class="d-block text-{{ $balanceChange >= 0 ? 'success' : 'danger' }}">
                            {{ $balanceChange >= 0 ? '+' : '' }}{{ number_format($balanceChange) }} ₫
                            ({{ number_format($changePercent, 1) }}%)
                        </small>
                    </div>
                </td>
                <td>
                    <div class="text-center">
                        @if($subCampaign->total_bets > 0)
                            <div class="mb-1">
                                <span class="text-{{ $subCampaign->win_rate >= 50 ? 'success' : 'danger' }}">
                                    {{ number_format($subCampaign->win_rate, 1) }}%
                                </span>
                                <small class="text-muted">win rate</small>
                            </div>
                            <small class="text-muted">
                                {{ $subCampaign->total_bets }} bets
                            </small>
                        @else
                            <small class="text-muted">Chưa có dữ liệu</small>
                        @endif
                    </div>
                </td>
                <td>
                    <span class="badge badge-light">{{ $subCampaign->betting_strategy }}</span>
                    @if($subCampaign->strategy_config)
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="tooltip" title="Xem cấu hình">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    @endif
                </td>
                <td>
                    <small class="text-muted">
                        {{ $subCampaign->start_date->format('d/m/Y') }}
                        @if($subCampaign->end_date)
                            <br>→ {{ $subCampaign->end_date->format('d/m/Y') }}
                        @endif
                    </small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        @if($subCampaign->status === 'pending')
                            <button type="button" class="btn btn-outline-success" onclick="startSubCampaign({{ $subCampaign->id }})" title="Khởi động">
                                <i class="fas fa-play"></i>
                            </button>
                        @elseif($subCampaign->status === 'active')
                            <button type="button" class="btn btn-outline-warning" onclick="pauseSubCampaign({{ $subCampaign->id }})" title="Tạm dừng">
                                <i class="fas fa-pause"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="stopSubCampaign({{ $subCampaign->id }})" title="Dừng">
                                <i class="fas fa-stop"></i>
                            </button>
                        @elseif($subCampaign->status === 'paused')
                            <button type="button" class="btn btn-outline-success" onclick="resumeSubCampaign({{ $subCampaign->id }})" title="Tiếp tục">
                                <i class="fas fa-play"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="stopSubCampaign({{ $subCampaign->id }})" title="Dừng">
                                <i class="fas fa-stop"></i>
                            </button>
                        @endif

                        <button type="button" class="btn btn-outline-info" onclick="viewSubCampaignDetails({{ $subCampaign->id }})" title="Chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>

                        @if(in_array($subCampaign->status, ['pending', 'paused']))
                            <button type="button" class="btn btn-outline-primary" onclick="editSubCampaign({{ $subCampaign->id }})" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                        @endif

                        @if($subCampaign->status === 'pending')
                            <button type="button" class="btn btn-outline-danger" onclick="deleteSubCampaign({{ $subCampaign->id }})" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Chưa có sub-campaigns nào</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubCampaignModal">
                        Tạo Sub-Campaign đầu tiên
                    </button>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script>
// Sub-campaign management functions
function startSubCampaign(subCampaignId) {
    if (confirm('Bạn có chắc muốn khởi động sub-campaign này?')) {
        fetch(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}/start`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi khởi động sub-campaign');
        });
    }
}

function pauseSubCampaign(subCampaignId) {
    if (confirm('Bạn có chắc muốn tạm dừng sub-campaign này?')) {
        fetch(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}/pause`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    }
}

function resumeSubCampaign(subCampaignId) {
    if (confirm('Bạn có chắc muốn tiếp tục sub-campaign này?')) {
        fetch(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}/resume`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    }
}

function stopSubCampaign(subCampaignId) {
    if (confirm('Bạn có chắc muốn dừng sub-campaign này? Số dư còn lại sẽ được trả về campaign chính.')) {
        fetch(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}/stop`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    }
}

function deleteSubCampaign(subCampaignId) {
    if (confirm('Bạn có chắc muốn xóa sub-campaign này? Hành động này không thể hoàn tác.')) {
        fetch(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    }
}

function viewSubCampaignDetails(subCampaignId) {
    // Open modal or navigate to details page
    window.open(`/campaigns/{{ $campaign->id }}/sub-campaigns/${subCampaignId}`, '_blank');
}

function editSubCampaign(subCampaignId) {
    // Open edit modal
    // Implementation depends on your edit modal structure
    console.log('Edit sub-campaign:', subCampaignId);
}
</script>
@endpush
