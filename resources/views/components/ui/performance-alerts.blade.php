{{-- Micro-task 2.3.1.3: Performance alerts (3h) --}}
@props([
    'campaign' => null,
    'alerts' => [],
    'summary' => [],
    'showActions' => true,
    'compact' => false
])

@php
    $alertIcons = [
        'critical' => '🔴',
        'warning' => '🟡',
        'info' => '🔵'
    ];

    $alertColors = [
        'critical' => 'danger',
        'warning' => 'warning',
        'info' => 'info'
    ];
@endphp

<div class="performance-alerts" data-campaign-id="{{ $campaign?->id }}">
    @if($compact)
        {{-- Compact view for dashboard overview --}}
        @if(count($alerts) > 0)
            <div class="alert alert-{{ $alertColors[$alerts[0]['type']] ?? 'secondary' }} p-2 mb-2">
                <div class="d-flex align-items-center">
                    <span class="me-2">{{ $alertIcons[$alerts[0]['type']] ?? '⚪' }}</span>
                    <div class="flex-grow-1">
                        <strong>{{ $alerts[0]['title'] }}</strong>
                        <div class="small">{{ $alerts[0]['message'] }}</div>
                    </div>
                    @if(count($alerts) > 1)
                        <span class="badge bg-secondary">+{{ count($alerts) - 1 }}</span>
                    @endif
                </div>
            </div>
        @else
            <div class="text-muted small">
                <i class="fas fa-check-circle text-success me-1"></i>
                Không có alerts
            </div>
        @endif
    @else
        {{-- Full view for detailed monitoring --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Performance Alerts
                    @if($campaign)
                        - {{ $campaign->name }}
                    @endif
                </h6>

                @if($showActions && $campaign)
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary"
                                onclick="refreshAlerts({{ $campaign->id }})">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success"
                                onclick="acknowledgeAlerts({{ $campaign->id }})">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                @endif
            </div>

            <div class="card-body">
                @if(count($alerts) > 0)
                    {{-- Alert summary --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <span class="text-danger me-2">🔴</span>
                                <div>
                                    <div class="fw-bold">{{ $summary['critical_alerts'] ?? 0 }}</div>
                                    <div class="small text-muted">Critical</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <span class="text-warning me-2">🟡</span>
                                <div>
                                    <div class="fw-bold">{{ $summary['warning_alerts'] ?? 0 }}</div>
                                    <div class="small text-muted">Warning</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <span class="text-info me-2">🔵</span>
                                <div>
                                    <div class="fw-bold">{{ $summary['info_alerts'] ?? 0 }}</div>
                                    <div class="small text-muted">Info</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @if($summary['action_required'] ?? false)
                                <div class="badge bg-danger">Action Required</div>
                            @else
                                <div class="badge bg-success">No Action Needed</div>
                            @endif
                        </div>
                    </div>

                    {{-- Alert list --}}
                    <div class="alerts-list">
                        @foreach($alerts as $index => $alert)
                            <div class="alert alert-{{ $alertColors[$alert['type']] ?? 'secondary' }}
                                        @if($alert['type'] === 'critical') border-start border-danger border-3 @endif">
                                <div class="d-flex align-items-start">
                                    <span class="me-3 fs-5">{{ $alertIcons[$alert['type']] ?? '⚪' }}</span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">{{ $alert['title'] }}</h6>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($alert['timestamp'])->diffForHumans() }}
                                            </small>
                                        </div>

                                        <p class="mb-1">{{ $alert['message'] }}</p>

                                        @if(isset($alert['value']) && isset($alert['threshold']))
                                            <div class="small mb-2">
                                                <strong>Giá trị hiện tại:</strong> {{ $alert['value'] }}
                                                <strong class="ms-3">Ngưỡng:</strong> {{ $alert['threshold'] }}
                                            </div>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-secondary me-2">{{ ucfirst($alert['category'] ?? 'other') }}</span>
                                                <span class="badge bg-outline-secondary">{{ ucfirst($alert['priority'] ?? 'medium') }}</span>
                                            </div>

                                            @if(isset($alert['action_required']) && $alert['action_required'] !== 'none')
                                                <div class="text-end">
                                                    @switch($alert['action_required'])
                                                        @case('auto_stop')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-stop me-1"></i>Auto Stop Required
                                                            </span>
                                                            @break
                                                        @case('urgent_review')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-eye me-1"></i>Urgent Review
                                                            </span>
                                                            @break
                                                        @case('review')
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-search me-1"></i>Review Needed
                                                            </span>
                                                            @break
                                                    @endswitch
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($showActions && $campaign)
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary btn-sm me-2"
                                    onclick="viewAlertHistory({{ $campaign->id }})">
                                <i class="fas fa-history me-1"></i>Lịch sử Alerts
                            </button>
                            <button type="button" class="btn btn-success btn-sm"
                                    onclick="acknowledgeAllAlerts({{ $campaign->id }})">
                                <i class="fas fa-check-double me-1"></i>Xác nhận tất cả
                            </button>
                        </div>
                    @endif
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6>Không có alerts nào</h6>
                        <p class="mb-0">Campaign đang hoạt động bình thường</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@if(!$compact && $showActions)
    <script>
    function refreshAlerts(campaignId) {
        // Show loading state
        const alertsContainer = document.querySelector(`[data-campaign-id="${campaignId}"]`);
        if (alertsContainer) {
            alertsContainer.style.opacity = '0.6';
        }

        fetch(`/performance-alerts/campaigns/${campaignId}/alerts`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the alerts component
                    location.reload(); // Simple reload for now
                }
            })
            .catch(error => {
                console.error('Error refreshing alerts:', error);
                alert('Lỗi khi refresh alerts');
            })
            .finally(() => {
                if (alertsContainer) {
                    alertsContainer.style.opacity = '1';
                }
            });
    }

    function acknowledgeAlerts(campaignId, alertTypes = []) {
        if (!confirm('Xác nhận đã xem và xử lý alerts này?')) {
            return;
        }

        const data = alertTypes.length > 0 ?
            { alert_types: alertTypes } :
            { acknowledge_all: true };

        fetch(`/performance-alerts/campaigns/${campaignId}/acknowledge-alerts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Alerts đã được xác nhận');
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error acknowledging alerts:', error);
            alert('Lỗi khi xác nhận alerts');
        });
    }

    function acknowledgeAllAlerts(campaignId) {
        acknowledgeAlerts(campaignId);
    }

    function viewAlertHistory(campaignId) {
        fetch(`/performance-alerts/campaigns/${campaignId}/alert-history`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show history in modal or new page
                    showAlertHistoryModal(data.data);
                }
            })
            .catch(error => {
                console.error('Error viewing alert history:', error);
                alert('Lỗi khi xem lịch sử alerts');
            });
    }

    function showAlertHistoryModal(historyData) {
        // Simple implementation - you can enhance this with a proper modal
        const historyText = historyData.history.map(entry =>
            `${entry.timestamp}: ${entry.alert_count} alerts`
        ).join('\n');

        alert(`Lịch sử Alerts:\n\n${historyText}`);
    }
    </script>
@endif

<style>
.performance-alerts .alert {
    border-left: 4px solid transparent;
}

.performance-alerts .alert-danger {
    border-left-color: #dc3545;
}

.performance-alerts .alert-warning {
    border-left-color: #ffc107;
}

.performance-alerts .alert-info {
    border-left-color: #0dcaf0;
}

.alerts-list .alert:last-child {
    margin-bottom: 0;
}

.badge.bg-outline-secondary {
    color: #6c757d;
    border: 1px solid #6c757d;
    background-color: transparent;
}
</style>
