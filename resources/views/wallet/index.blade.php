@extends('layouts.app')

@section('title', 'Quản Lý Ví')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Quản Lý Ví</h1>
        </div>
    </div>

    <!-- Wallet Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Ví Thật</h5>
                    <h3 class="text-primary">{{ number_format($wallet->real_balance) }} VND</h3>
                    <small class="text-muted">Có thể rút được</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Ví Ảo</h5>
                    <h3 class="text-info">{{ number_format($wallet->virtual_balance) }} VND</h3>
                    <small class="text-muted">Dùng để test</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Ví Bonus</h5>
                    <h3 class="text-warning">{{ number_format($wallet->bonus_balance) }} VND</h3>
                    <small class="text-muted">Từ khuyến mãi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Tổng Cộng</h5>
                    <h3 class="text-success">{{ number_format($wallet->total_balance) }} VND</h3>
                    <small class="text-muted">Tất cả ví</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-md-4">
            <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#depositModal">
                <i class="fas fa-plus"></i> Nạp Tiền
            </button>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#withdrawModal">
                <i class="fas fa-minus"></i> Rút Tiền
            </button>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#transferModal">
                <i class="fas fa-exchange-alt"></i> Chuyển Đổi
            </button>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Lịch Sử Giao Dịch</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Thời Gian</th>
                                    <th>Loại</th>
                                    <th>Số Tiền</th>
                                    <th>Trạng Thái</th>
                                    <th>Mô Tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge badge-secondary">{{ ucfirst($transaction->type) }}</span>
                                        </td>
                                        <td class="{{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }} VND
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Chưa có giao dịch nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('wallet.deposit') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nạp Tiền</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="amount">Số Tiền (VND)</label>
                        <input type="number" name="amount" class="form-control" min="10000" max="10000000" required>
                        <small class="form-text text-muted">Tối thiểu 10,000 VND - Tối đa 10,000,000 VND</small>
                    </div>
                    <div class="form-group">
                        <label for="gateway">Phương Thức Thanh Toán</label>
                        <select name="gateway" class="form-control" required>
                            <option value="">Chọn phương thức</option>
                            <option value="vnpay">VNPay</option>
                            <option value="momo">MoMo</option>
                            <option value="bank_transfer">Chuyển Khoản Ngân Hàng</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Nạp Tiền</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('wallet.withdraw') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Rút Tiền</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="amount">Số Tiền (VND)</label>
                        <input type="number" name="amount" class="form-control" min="50000" max="{{ $wallet->real_balance }}" required>
                        <small class="form-text text-muted">
                            Tối thiểu 50,000 VND - Có thể rút: {{ number_format($wallet->real_balance) }} VND
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="bank_name">Tên Ngân Hàng</label>
                        <input type="text" name="bank_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bank_account">Số Tài Khoản</label>
                        <input type="text" name="bank_account" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">Rút Tiền</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('wallet.transfer') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Chuyển Đổi Ví</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="from_type">Từ Ví</label>
                        <select name="from_type" class="form-control" required>
                            <option value="">Chọn ví nguồn</option>
                            <option value="real">Ví Thật ({{ number_format($wallet->real_balance) }} VND)</option>
                            <option value="virtual">Ví Ảo ({{ number_format($wallet->virtual_balance) }} VND)</option>
                            <option value="bonus">Ví Bonus ({{ number_format($wallet->bonus_balance) }} VND)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="to_type">Đến Ví</label>
                        <select name="to_type" class="form-control" required>
                            <option value="">Chọn ví đích</option>
                            <option value="real">Ví Thật</option>
                            <option value="virtual">Ví Ảo</option>
                            <option value="bonus">Ví Bonus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Số Tiền (VND)</label>
                        <input type="number" name="amount" class="form-control" min="1000" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Ghi Chú (Tùy Chọn)</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info">Chuyển Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
