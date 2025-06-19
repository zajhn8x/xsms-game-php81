@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shield-alt"></i> Risk Management</h2>
                <div>
                    @if($risk_rules->count() == 0)
                        <button class="btn btn-warning" id="setupDefaultsBtn">
                            <i class="fas fa-magic"></i> Thiết lập Rules mặc định
                        </button>
                    @endif
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                        <i class="fas fa-plus"></i> Thêm Rule mới
                    </button>
                </div>
            </div>

            <!-- Current Risk Status -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Trạng thái Risk hiện tại</h5>
                        </div>
                        <div class="card-body">
                            <div id="risk-status" class="text-center">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Checking...</span>
                                </div>
                                <p class="mt-2">Đang kiểm tra...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Rules List -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Danh sách Risk Rules</h5>
                        </div>
                        <div class="card-body">
                            @if($risk_rules->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên Rule</th>
                                                <th>Loại</th>
                                                <th>Ngưỡng</th>
                                                <th>Trạng thái</th>
                                                <th>Triggered</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($risk_rules as $rule)
                                            <tr>
                                                <td>
                                                    <strong>{{ $rule->rule_name }}</strong>
                                                    @if($rule->is_global)
                                                        <span class="badge bg-info">Global</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $rule->rule_type)) }}</span>
                                                </td>
                                                <td>
                                                    @if($rule->threshold_amount)
                                                        {{ number_format($rule->threshold_amount) }} VND
                                                    @elseif($rule->threshold_count)
                                                        {{ $rule->threshold_count }} lần
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-rule" type="checkbox"
                                                               data-rule-id="{{ $rule->id }}"
                                                               {{ $rule->is_active ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $rule->trigger_count > 0 ? 'warning' : 'success' }}">
                                                        {{ $rule->trigger_count }} lần
                                                    </span>
                                                    @if($rule->last_triggered_at)
                                                        <br><small class="text-muted">{{ $rule->last_triggered_at->diffForHumans() }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary view-rule"
                                                                data-rule-id="{{ $rule->id }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning edit-rule"
                                                                data-rule-id="{{ $rule->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger delete-rule"
                                                                data-rule-id="{{ $rule->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{ $risk_rules->links() }}
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-shield-alt fa-3x mb-3"></i>
                                    <h5>Chưa có Risk Rules nào</h5>
                                    <p>Thiết lập rules để bảo vệ tài khoản của bạn khỏi rủi ro</p>
                                    <button class="btn btn-warning" id="setupDefaultsBtn2">
                                        <i class="fas fa-magic"></i> Thiết lập Rules mặc định
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-bar"></i> Thống kê</h6>
                        </div>
                        <div class="card-body" id="risk-statistics">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <p class="mt-2">Đang tải...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Templates -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-layer-group"></i> Templates</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-success apply-template" data-template="conservative">
                                    <i class="fas fa-shield-alt"></i> Bảo thủ
                                </button>
                                <button class="btn btn-outline-warning apply-template" data-template="moderate">
                                    <i class="fas fa-balance-scale"></i> Vừa phải
                                </button>
                                <button class="btn btn-outline-danger apply-template" data-template="aggressive">
                                    <i class="fas fa-rocket"></i> Tích cực
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Chọn template phù hợp với phong cách trading của bạn
                            </small>
                        </div>
                    </div>

                    <!-- Help -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-question-circle"></i> Hướng dẫn</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-info-circle text-info"></i> <strong>Daily Loss Limit:</strong> Giới hạn thua trong ngày</li>
                                <li><i class="fas fa-info-circle text-info"></i> <strong>Consecutive Loss:</strong> Giới hạn thua liên tiếp</li>
                                <li><i class="fas fa-info-circle text-info"></i> <strong>Balance Threshold:</strong> Ngưỡng số dư tối thiểu</li>
                                <li><i class="fas fa-info-circle text-info"></i> <strong>Win Streak Protection:</strong> Bảo vệ chuỗi thắng</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Rule Modal -->
<div class="modal fade" id="createRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo Risk Rule mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createRuleForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tên Rule</label>
                                <input type="text" class="form-control" name="rule_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Loại Rule</label>
                                <select class="form-select" name="rule_type" required>
                                    <option value="">Chọn loại rule</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngưỡng số tiền (VND)</label>
                                <input type="number" class="form-control" name="threshold_amount" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngưỡng số lần</label>
                                <input type="number" class="form-control" name="threshold_count" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Khung thời gian (giờ)</label>
                                <input type="number" class="form-control" name="time_window_hours" min="1" max="168" value="24">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_global">
                                    <label class="form-check-label">
                                        Áp dụng cho tất cả campaigns
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>

                    <!-- Dynamic conditions and actions will be loaded here -->
                    <div id="dynamic-rule-config"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Load initial data
    loadRiskStatus();
    loadStatistics();
    loadRuleTypes();

    function loadRiskStatus() {
        $.get('/risk-management/api/check')
        .done(function(response) {
            const html = `
                <div class="alert alert-${response.is_safe ? 'success' : 'danger'} mb-0">
                    <h5><i class="fas fa-${response.is_safe ? 'check-circle' : 'exclamation-triangle'}"></i>
                        ${response.is_safe ? 'An toàn' : 'Cảnh báo Risk!'}</h5>
                    <p class="mb-0">${response.message}</p>
                </div>
            `;
            $('#risk-status').html(html);
        })
        .fail(function() {
            $('#risk-status').html(`
                <div class="alert alert-warning mb-0">
                    <h5><i class="fas fa-question-circle"></i> Không thể kiểm tra</h5>
                    <p class="mb-0">Lỗi khi kiểm tra trạng thái risk</p>
                </div>
            `);
        });
    }

    function loadStatistics() {
        $.get('/risk-management/api/statistics')
        .done(function(response) {
            const stats = response.statistics;
            let html = `
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary">${stats.total_rules}</h4>
                        <small>Tổng Rules</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success">${stats.active_rules}</h4>
                        <small>Đang hoạt động</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-12">
                        <h5 class="text-warning">${stats.triggered_today}</h5>
                        <small>Triggered hôm nay</small>
                    </div>
                </div>
            `;

            if (stats.most_triggered) {
                html += `
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">Rule trigger nhiều nhất:</small>
                        <br><strong>${stats.most_triggered.rule_name}</strong>
                        <br><span class="badge bg-warning">${stats.most_triggered.trigger_count} lần</span>
                    </div>
                `;
            }

            $('#risk-statistics').html(html);
        });
    }

    function loadRuleTypes() {
        $.get('/risk-management/api/rule-types')
        .done(function(response) {
            let options = '<option value="">Chọn loại rule</option>';
            Object.keys(response.rule_types).forEach(function(key) {
                const ruleType = response.rule_types[key];
                options += `<option value="${key}">${ruleType.name}</option>`;
            });
            $('select[name="rule_type"]').html(options);
        });
    }

    // Setup default rules
    $(document).on('click', '#setupDefaultsBtn, #setupDefaultsBtn2', function() {
        if (confirm('Bạn có chắc muốn thiết lập các risk rules mặc định?')) {
            $.post('/risk-management/setup-defaults', {
                _token: $('meta[name="csrf-token"]').attr('content')
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            });
        }
    });

    // Toggle rule
    $(document).on('change', '.toggle-rule', function() {
        const ruleId = $(this).data('rule-id');

        $.post(`/risk-management/${ruleId}/toggle`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                loadRiskStatus();
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Delete rule
    $(document).on('click', '.delete-rule', function() {
        const ruleId = $(this).data('rule-id');

        if (confirm('Bạn có chắc muốn xóa risk rule này?')) {
            $.ajax({
                url: `/risk-management/${ruleId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            })
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            });
        }
    });

    // Apply template
    $(document).on('click', '.apply-template', function() {
        const template = $(this).data('template');

        if (confirm(`Bạn có chắc muốn áp dụng template "${template}"? Điều này sẽ tạo các rules mới.`)) {
            $.get('/risk-management/api/templates')
            .done(function(response) {
                const templateData = response.templates[template];
                if (templateData && templateData.rules) {
                    // Create rules from template
                    let created = 0;
                    templateData.rules.forEach(function(ruleData) {
                        $.post('/risk-management', Object.assign(ruleData, {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }))
                        .done(function() {
                            created++;
                            if (created === templateData.rules.length) {
                                toastr.success(`Đã tạo ${created} risk rules từ template ${templateData.name}`);
                                location.reload();
                            }
                        });
                    });
                }
            });
        }
    });

    // Create rule form
    $('#createRuleForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        // Build conditions and actions based on rule type
        const ruleType = formData.get('rule_type');
        const conditions = [];
        const actions = [];

        // Simple default conditions and actions
        if (ruleType === 'daily_loss_limit') {
            conditions.push({
                type: 'daily_loss_amount',
                value: parseInt(formData.get('threshold_amount')) || 0
            });
            actions.push({ type: 'pause_campaigns', params: {} });
            actions.push({ type: 'send_notification', params: { message: 'Daily loss limit reached' } });
        }

        formData.append('conditions', JSON.stringify(conditions));
        formData.append('actions', JSON.stringify(actions));

        $.post('/risk-management', Object.fromEntries(formData))
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#createRuleModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Lỗi khi tạo risk rule');
        });
    });

    // Auto refresh status every 60 seconds
    setInterval(function() {
        loadRiskStatus();
        loadStatistics();
    }, 60000);
});
</script>
@endpush
@endsection
