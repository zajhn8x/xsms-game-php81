@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-shield-alt"></i> Xác thực hai yếu tố (2FA)</h4>
                </div>

                <div class="card-body">
                    @if($two_factor_enabled)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>2FA đã được kích hoạt!</strong> Tài khoản của bạn được bảo vệ bởi xác thực hai yếu tố.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Trạng thái bảo mật</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Authenticator App (TOTP)</span>
                                        <span class="badge bg-success">Đã kích hoạt</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>SMS Backup</span>
                                        <span class="badge bg-{{ $phone_verified ? 'success' : 'secondary' }}">
                                            {{ $phone_verified ? 'Đã xác thực' : 'Chưa xác thực' }}
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Recovery Codes</span>
                                        <span class="badge bg-info">{{ $recovery_codes_count }} codes</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="col-md-6">
                                <h5>Hành động</h5>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" onclick="generateRecoveryCodes()">
                                        <i class="fas fa-sync"></i> Tạo mã khôi phục mới
                                    </button>

                                    <button class="btn btn-warning" onclick="showDisable2FA()">
                                        <i class="fas fa-times"></i> Tắt 2FA
                                    </button>
                                </div>
                            </div>
                        </div>

                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Khuyến nghị bảo mật:</strong> Kích hoạt 2FA để bảo vệ tài khoản của bạn tốt hơn.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Tại sao nên sử dụng 2FA?</h5>
                                <ul>
                                    <li>Bảo vệ tài khoản khỏi truy cập trái phép</li>
                                    <li>Bảo vệ tài sản trong ví điện tử</li>
                                    <li>Tuân thủ best practices bảo mật</li>
                                    <li>Cần thiết cho các giao dịch lớn</li>
                                </ul>

                                <button class="btn btn-success btn-lg" onclick="setup2FA()">
                                    <i class="fas fa-shield-alt"></i> Kích hoạt 2FA ngay
                                </button>
                            </div>

                            <div class="col-md-6">
                                <h5>Các phương thức hỗ trợ</h5>
                                <div class="list-group">
                                    @if(in_array('totp', $available_methods))
                                    <div class="list-group-item">
                                        <i class="fas fa-mobile-alt"></i>
                                        <strong>Authenticator App</strong>
                                        <p class="mb-0 text-muted">Google Authenticator, Authy, v.v.</p>
                                    </div>
                                    @endif

                                    @if(in_array('sms', $available_methods))
                                    <div class="list-group-item">
                                        <i class="fas fa-sms"></i>
                                        <strong>SMS Backup</strong>
                                        <p class="mb-0 text-muted">Nhận mã qua tin nhắn</p>
                                    </div>
                                    @endif

                                    <div class="list-group-item">
                                        <i class="fas fa-envelope"></i>
                                        <strong>Email Backup</strong>
                                        <p class="mb-0 text-muted">Nhận mã qua email</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Setup 2FA Modal -->
<div class="modal fade" id="setup2FAModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thiết lập 2FA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qr-step" style="display: none;">
                    <h6>Bước 1: Quét mã QR</h6>
                    <div class="text-center">
                        <div id="qr-code"></div>
                        <p class="mt-2">Hoặc nhập thủ công: <code id="manual-key"></code></p>
                    </div>
                </div>

                <div id="verify-step" style="display: none;">
                    <h6>Bước 2: Xác nhận mã</h6>
                    <div class="mb-3">
                        <label class="form-label">Nhập mã 6 số từ app Authenticator:</label>
                        <input type="text" class="form-control" id="verify-code" maxlength="6" placeholder="123456">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="setup-next-btn" onclick="nextStep()">Tiếp theo</button>
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
<div class="modal fade" id="disable2FAModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tắt 2FA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tắt 2FA sẽ làm giảm bảo mật tài khoản của bạn.
                </div>
                <div class="mb-3">
                    <label class="form-label">Nhập mật khẩu để xác nhận:</label>
                    <input type="password" class="form-control" id="disable-password" placeholder="Mật khẩu">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="disable2FA()">Tắt 2FA</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentStep = 'setup';
let setupData = null;

function setup2FA() {
    fetch('/two-factor/enable-totp', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setupData = data.data;
            document.getElementById('qr-code').innerHTML = `<img src="${data.data.qr_code_url}" alt="QR Code">`;
            document.getElementById('manual-key').textContent = data.data.manual_entry_key;

            document.getElementById('qr-step').style.display = 'block';
            document.getElementById('verify-step').style.display = 'none';
            document.getElementById('setup-next-btn').textContent = 'Tiếp theo';

            currentStep = 'qr';
            new bootstrap.Modal(document.getElementById('setup2FAModal')).show();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}

function nextStep() {
    if (currentStep === 'qr') {
        document.getElementById('qr-step').style.display = 'none';
        document.getElementById('verify-step').style.display = 'block';
        document.getElementById('setup-next-btn').textContent = 'Xác nhận';
        currentStep = 'verify';
    } else if (currentStep === 'verify') {
        const code = document.getElementById('verify-code').value;
        if (!code || code.length !== 6) {
            alert('Vui lòng nhập mã 6 số');
            return;
        }

        confirmSetup(code);
    }
}

function confirmSetup(code) {
    fetch('/two-factor/confirm-totp', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ code: code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('2FA đã được kích hoạt thành công!\n\nMã khôi phục của bạn:\n' + data.recovery_codes.join('\n'));
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}

function showDisable2FA() {
    new bootstrap.Modal(document.getElementById('disable2FAModal')).show();
}

function disable2FA() {
    const password = document.getElementById('disable-password').value;
    if (!password) {
        alert('Vui lòng nhập mật khẩu');
        return;
    }

    fetch('/two-factor/disable', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('2FA đã được tắt');
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}

function generateRecoveryCodes() {
    const password = prompt('Nhập mật khẩu để tạo mã khôi phục mới:');
    if (!password) return;

    fetch('/two-factor/recovery-codes', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mã khôi phục mới:\n\n' + data.recovery_codes.join('\n') + '\n\nVui lòng lưu lại các mã này ở nơi an toàn!');
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}
</script>
@endpush
