@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-university"></i> Thông Tin Chuyển Khoản</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Hướng Dẫn Chuyển Khoản</h5>
                        <p class="mb-0">Vui lòng chuyển khoản theo thông tin bên dưới và ghi đúng nội dung để hệ thống tự động xử lý.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <i class="fas fa-credit-card"></i> Thông Tin Giao Dịch
                                    </h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Mã giao dịch:</strong></td>
                                            <td class="text-success">{{ $transaction->transaction_id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số tiền:</strong></td>
                                            <td class="text-success">{{ number_format($transaction->amount) }} VND</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phí giao dịch:</strong></td>
                                            <td class="text-muted">{{ number_format($fee ?? 0) }} VND</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Thời gian tạo:</strong></td>
                                            <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Hết hạn:</strong></td>
                                            <td class="text-warning">{{ $transaction->created_at->addHours(24)->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-university"></i> {{ $bankInfo['name'] }}
                                    </h5>
                                    <table class="table table-borderless text-white">
                                        <tr>
                                            <td><strong>Số tài khoản:</strong></td>
                                            <td>
                                                <span id="accountNumber">{{ $bankInfo['account_number'] }}</span>
                                                <button class="btn btn-sm btn-outline-light ml-2" onclick="copyToClipboard('accountNumber')" title="Copy">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Chủ tài khoản:</strong></td>
                                            <td>{{ $bankInfo['account_name'] }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Chi nhánh:</strong></td>
                                            <td>{{ $bankInfo['branch'] }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>SWIFT Code:</strong></td>
                                            <td>{{ $bankInfo['swift_code'] }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <h5><i class="fas fa-exclamation-triangle"></i> Nội Dung Chuyển Khoản (Bắt Buộc)</h5>
                        <div class="input-group">
                            <input type="text" class="form-control font-weight-bold"
                                   id="transferContent"
                                   value="XSMB {{ $transaction->transaction_id }}"
                                   readonly>
                            <div class="input-group-append">
                                <button class="btn btn-warning" onclick="copyToClipboard('transferContent')" title="Copy nội dung">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">
                            ⚠️ Vui lòng copy chính xác nội dung trên khi chuyển khoản để hệ thống tự động xử lý.
                        </small>
                    </div>

                    <div class="alert alert-success">
                        <h5><i class="fas fa-clock"></i> Thời Gian Xử Lý</h5>
                        <ul class="mb-0">
                            <li>Trong giờ hành chính (8:00 - 17:00): <strong>15-30 phút</strong></li>
                            <li>Ngoài giờ hành chính và cuối tuần: <strong>1-24 giờ</strong></li>
                            <li>Nếu quá 24 giờ chưa được xử lý, vui lòng liên hệ hỗ trợ</li>
                        </ul>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('wallet.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left"></i> Quay Lại Ví
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary btn-block" onclick="checkTransactionStatus()">
                                <i class="fas fa-sync-alt"></i> Kiểm Tra Trạng Thái
                            </button>
                        </div>
                    </div>

                    <!-- QR Code for Mobile Banking (if available) -->
                    @if(isset($qrCode))
                    <div class="text-center mt-4">
                        <h5>Quét QR Code để chuyển khoản nhanh</h5>
                        <img src="{{ $qrCode }}" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                        <p class="text-muted">Sử dụng app ngân hàng để quét QR code</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-question-circle"></i> Hướng Dẫn Chi Tiết</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="h4 mb-0">1</span>
                                </div>
                                <h6 class="mt-2">Mở App Banking</h6>
                                <p class="text-muted small">Mở ứng dụng ngân hàng hoặc internet banking</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="h4 mb-0">2</span>
                                </div>
                                <h6 class="mt-2">Nhập Thông Tin</h6>
                                <p class="text-muted small">Copy thông tin tài khoản và nội dung chuyển khoản</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="h4 mb-0">3</span>
                                </div>
                                <h6 class="mt-2">Hoàn Thành</h6>
                                <p class="text-muted small">Xác nhận giao dịch và chờ hệ thống xử lý</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');

    // Show success message
    const originalText = element.value;
    toastr.success('Đã copy: ' + originalText, 'Thành công');
}

function checkTransactionStatus() {
    fetch(`/api/wallet/transaction/{{ $transaction->transaction_id }}/status`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                toastr.success('Giao dịch đã được xử lý thành công!', 'Cập nhật');
                setTimeout(() => {
                    window.location.href = '{{ route("wallet.index") }}';
                }, 2000);
            } else if (data.status === 'failed') {
                toastr.error('Giao dịch thất bại. Vui lòng thử lại.', 'Lỗi');
            } else {
                toastr.info('Giao dịch đang được xử lý...', 'Thông tin');
            }
        })
        .catch(error => {
            toastr.error('Có lỗi khi kiểm tra trạng thái', 'Lỗi');
        });
}

// Auto refresh status every 30 seconds
setInterval(checkTransactionStatus, 30000);
</script>
@endsection
