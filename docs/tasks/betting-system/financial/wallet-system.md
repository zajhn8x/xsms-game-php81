# Hệ Thống Ví Điện Tử

## Mục tiêu
Xây dựng hệ thống ví điện tử an toàn và đáng tin cậy để quản lý số dư, giao dịch nạp/rút tiền cho người dùng.

## Prerequisites
- User authentication đã hoạt động
- Database đã sẵn sàng
- Hệ thống phân quyền đã được thiết lập

## Tính năng Chính

### 1. Wallet Management
- **Virtual Balance**: Số dư ảo cho việc test
- **Real Balance**: Số dư thật có thể rút ra
- **Frozen Balance**: Số dư bị đóng băng (đang trong giao dịch)
- **Bonus Balance**: Số dư khuyến mãi

### 2. Transaction Types
- **Deposit**: Nạp tiền vào ví
- **Withdrawal**: Rút tiền từ ví
- **Transfer**: Chuyển tiền giữa các loại balance
- **Campaign**: Chi tiêu/thu từ chiến dịch
- **Bonus**: Thưởng từ hệ thống
- **Refund**: Hoàn tiền

## Các Bước Thực Hiện

### Bước 1: Tạo Database Schema
```php
// Migration: create_wallets_table
Schema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->decimal('real_balance', 15, 2)->default(0);
    $table->decimal('virtual_balance', 15, 2)->default(0);
    $table->decimal('frozen_balance', 15, 2)->default(0);
    $table->decimal('bonus_balance', 15, 2)->default(0);
    $table->decimal('total_deposited', 15, 2)->default(0);
    $table->decimal('total_withdrawn', 15, 2)->default(0);
    $table->string('currency', 3)->default('VND');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_transaction_at')->nullable();
    $table->timestamps();
    
    $table->unique('user_id');
    $table->index(['user_id', 'is_active']);
});

// Migration: create_wallet_transactions_table
Schema::create('wallet_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
    $table->string('transaction_id')->unique();
    $table->enum('type', ['deposit', 'withdrawal', 'transfer', 'campaign', 'bonus', 'refund']);
    $table->enum('balance_type', ['real', 'virtual', 'frozen', 'bonus']);
    $table->decimal('amount', 15, 2);
    $table->decimal('fee', 15, 2)->default(0);
    $table->decimal('balance_before', 15, 2);
    $table->decimal('balance_after', 15, 2);
    $table->enum('status', ['pending', 'completed', 'failed', 'cancelled']);
    $table->string('gateway')->nullable(); // vnpay, momo, bank_transfer
    $table->string('gateway_transaction_id')->nullable();
    $table->text('description')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('related_id')->nullable(); // campaign_id, bet_id, etc.
    $table->string('related_type')->nullable(); // campaign, bet, etc.
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();
    
    $table->index(['wallet_id', 'status']);
    $table->index(['transaction_id']);
    $table->index(['gateway_transaction_id']);
    $table->index(['type', 'status']);
});
```

### Bước 2: Tạo Wallet Model
```php
// app/Models/Wallet.php
class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'real_balance', 'virtual_balance', 
        'frozen_balance', 'bonus_balance',
        'total_deposited', 'total_withdrawn',
        'currency', 'is_active', 'last_transaction_at'
    ];

    protected $casts = [
        'real_balance' => 'decimal:2',
        'virtual_balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Get total available balance
    public function getAvailableBalanceAttribute()
    {
        return $this->real_balance + $this->virtual_balance + $this->bonus_balance;
    }

    // Get total balance including frozen
    public function getTotalBalanceAttribute()
    {
        return $this->available_balance + $this->frozen_balance;
    }

    // Get usable balance for betting (real + bonus)
    public function getUsableBalanceAttribute()
    {
        return $this->real_balance + $this->bonus_balance;
    }
}

// app/Models/WalletTransaction.php
class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'transaction_id', 'type', 'balance_type',
        'amount', 'fee', 'balance_before', 'balance_after',
        'status', 'gateway', 'gateway_transaction_id',
        'description', 'metadata', 'related_id', 'related_type',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    // Generate unique transaction ID
    public static function generateTransactionId()
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }
}
```

### Bước 3: Tạo Wallet Service
```php
// app/Services/WalletService.php
class WalletService
{
    public function createWallet($userId)
    {
        return Wallet::create([
            'user_id' => $userId,
            'real_balance' => 0,
            'virtual_balance' => 1000000, // Give 1M virtual money for testing
            'frozen_balance' => 0,
            'bonus_balance' => 0
        ]);
    }

    public function getOrCreateWallet($userId)
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            [
                'real_balance' => 0,
                'virtual_balance' => 1000000,
                'frozen_balance' => 0,
                'bonus_balance' => 0
            ]
        );
    }

    public function deposit($userId, $amount, $gateway = null, $metadata = [])
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        return DB::transaction(function () use ($wallet, $amount, $gateway, $metadata) {
            $transaction = $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'balance_type' => 'real',
                'amount' => $amount,
                'balance_before' => $wallet->real_balance,
                'gateway' => $gateway,
                'metadata' => $metadata,
                'status' => 'pending'
            ]);

            return $transaction;
        });
    }

    public function processDeposit($transactionId, $gatewayTransactionId = null, $status = 'completed')
    {
        return DB::transaction(function () use ($transactionId, $gatewayTransactionId, $status) {
            $transaction = WalletTransaction::where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                throw new \Exception('Transaction already processed');
            }

            $wallet = $transaction->wallet;

            if ($status === 'completed') {
                $wallet->increment('real_balance', $transaction->amount);
                $wallet->increment('total_deposited', $transaction->amount);
                
                $transaction->update([
                    'status' => 'completed',
                    'balance_after' => $wallet->fresh()->real_balance,
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'processed_at' => now()
                ]);

                $wallet->update(['last_transaction_at' => now()]);
            } else {
                $transaction->update([
                    'status' => $status,
                    'processed_at' => now()
                ]);
            }

            return $transaction;
        });
    }

    public function withdraw($userId, $amount, $gateway = null, $metadata = [])
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        if ($wallet->real_balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($wallet, $amount, $gateway, $metadata) {
            // Freeze the amount first
            $wallet->decrement('real_balance', $amount);
            $wallet->increment('frozen_balance', $amount);

            $transaction = $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'balance_type' => 'real',
                'amount' => $amount,
                'balance_before' => $wallet->real_balance + $amount,
                'balance_after' => $wallet->fresh()->real_balance,
                'gateway' => $gateway,
                'metadata' => $metadata,
                'status' => 'pending'
            ]);

            return $transaction;
        });
    }

    public function processWithdrawal($transactionId, $gatewayTransactionId = null, $status = 'completed')
    {
        return DB::transaction(function () use ($transactionId, $gatewayTransactionId, $status) {
            $transaction = WalletTransaction::where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                throw new \Exception('Transaction already processed');
            }

            $wallet = $transaction->wallet;

            if ($status === 'completed') {
                $wallet->decrement('frozen_balance', $transaction->amount);
                $wallet->increment('total_withdrawn', $transaction->amount);
                
                $transaction->update([
                    'status' => 'completed',
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'processed_at' => now()
                ]);
            } else {
                // Refund to real balance if failed
                $wallet->increment('real_balance', $transaction->amount);
                $wallet->decrement('frozen_balance', $transaction->amount);
                
                $transaction->update([
                    'status' => $status,
                    'processed_at' => now()
                ]);
            }

            $wallet->update(['last_transaction_at' => now()]);
            return $transaction;
        });
    }

    public function transfer($userId, $fromType, $toType, $amount, $description = null)
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        $fromField = $fromType . '_balance';
        $toField = $toType . '_balance';
        
        if ($wallet->{$fromField} < $amount) {
            throw new \Exception("Insufficient {$fromType} balance");
        }

        return DB::transaction(function () use ($wallet, $fromType, $toType, $amount, $description, $fromField, $toField) {
            $balanceBefore = $wallet->{$fromField};
            
            $wallet->decrement($fromField, $amount);
            $wallet->increment($toField, $amount);

            $transaction = $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'transfer',
                'balance_type' => $fromType,
                'amount' => -$amount, // Negative for deduction
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->fresh()->{$fromField},
                'description' => $description ?: "Transfer from {$fromType} to {$toType}",
                'status' => 'completed',
                'processed_at' => now()
            ]);

            // Create corresponding credit transaction
            $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'transfer',
                'balance_type' => $toType,
                'amount' => $amount,
                'balance_before' => $wallet->{$toField} - $amount,
                'balance_after' => $wallet->{$toField},
                'description' => $description ?: "Transfer from {$fromType} to {$toType}",
                'status' => 'completed',
                'processed_at' => now()
            ]);

            $wallet->update(['last_transaction_at' => now()]);
            return $transaction;
        });
    }

    public function deductForBetting($userId, $amount, $campaignId)
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        // Use bonus balance first, then real balance
        $bonusUsed = min($amount, $wallet->bonus_balance);
        $realUsed = $amount - $bonusUsed;
        
        if ($wallet->usable_balance < $amount) {
            throw new \Exception('Insufficient balance for betting');
        }

        return DB::transaction(function () use ($wallet, $amount, $bonusUsed, $realUsed, $campaignId) {
            $transactions = [];
            
            if ($bonusUsed > 0) {
                $wallet->decrement('bonus_balance', $bonusUsed);
                $transactions[] = $this->createTransaction([
                    'wallet_id' => $wallet->id,
                    'type' => 'campaign',
                    'balance_type' => 'bonus',
                    'amount' => -$bonusUsed,
                    'balance_before' => $wallet->bonus_balance + $bonusUsed,
                    'balance_after' => $wallet->bonus_balance,
                    'description' => 'Betting deduction from bonus balance',
                    'related_id' => $campaignId,
                    'related_type' => 'campaign',
                    'status' => 'completed',
                    'processed_at' => now()
                ]);
            }
            
            if ($realUsed > 0) {
                $wallet->decrement('real_balance', $realUsed);
                $transactions[] = $this->createTransaction([
                    'wallet_id' => $wallet->id,
                    'type' => 'campaign',
                    'balance_type' => 'real',
                    'amount' => -$realUsed,
                    'balance_before' => $wallet->real_balance + $realUsed,
                    'balance_after' => $wallet->real_balance,
                    'description' => 'Betting deduction from real balance',
                    'related_id' => $campaignId,
                    'related_type' => 'campaign',
                    'status' => 'completed',
                    'processed_at' => now()
                ]);
            }

            $wallet->update(['last_transaction_at' => now()]);
            return $transactions;
        });
    }

    public function addWinnings($userId, $amount, $campaignId, $balanceType = 'real')
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        return DB::transaction(function () use ($wallet, $amount, $campaignId, $balanceType) {
            $balanceField = $balanceType . '_balance';
            
            $wallet->increment($balanceField, $amount);

            $transaction = $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'campaign',
                'balance_type' => $balanceType,
                'amount' => $amount,
                'balance_before' => $wallet->{$balanceField} - $amount,
                'balance_after' => $wallet->{$balanceField},
                'description' => 'Betting winnings',
                'related_id' => $campaignId,
                'related_type' => 'campaign',
                'status' => 'completed',
                'processed_at' => now()
            ]);

            $wallet->update(['last_transaction_at' => now()]);
            return $transaction;
        });
    }

    public function addBonus($userId, $amount, $description = 'System bonus')
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        return DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->increment('bonus_balance', $amount);

            $transaction = $this->createTransaction([
                'wallet_id' => $wallet->id,
                'type' => 'bonus',
                'balance_type' => 'bonus',
                'amount' => $amount,
                'balance_before' => $wallet->bonus_balance - $amount,
                'balance_after' => $wallet->bonus_balance,
                'description' => $description,
                'status' => 'completed',
                'processed_at' => now()
            ]);

            $wallet->update(['last_transaction_at' => now()]);
            return $transaction;
        });
    }

    private function createTransaction($data)
    {
        $data['transaction_id'] = WalletTransaction::generateTransactionId();
        
        return WalletTransaction::create($data);
    }

    public function getTransactionHistory($userId, $filters = [])
    {
        $wallet = $this->getOrCreateWallet($userId);
        
        $query = $wallet->transactions()->orderBy('created_at', 'desc');
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        return $query->paginate(20);
    }
}
```

### Bước 4: Tạo Wallet Controller
```php
// app/Http/Controllers/WalletController.php
class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
        $this->middleware('auth');
    }

    public function index()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->id());
        $recentTransactions = $this->walletService->getTransactionHistory(auth()->id())->take(10);
        
        return view('wallet.index', compact('wallet', 'recentTransactions'));
    }

    public function transactions(Request $request)
    {
        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);
        $transactions = $this->walletService->getTransactionHistory(auth()->id(), $filters);
        
        return view('wallet.transactions', compact('transactions', 'filters'));
    }

    public function deposit()
    {
        return view('wallet.deposit');
    }

    public function processDeposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000|max:50000000',
            'gateway' => 'required|in:vnpay,momo,bank_transfer'
        ]);

        try {
            $transaction = $this->walletService->deposit(
                auth()->id(),
                $validated['amount'],
                $validated['gateway']
            );

            // Redirect to payment gateway
            return $this->redirectToPaymentGateway($transaction, $validated['gateway']);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function withdraw()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->id());
        
        if ($wallet->real_balance < 50000) {
            return redirect()->route('wallet.index')
                ->with('error', 'Số dư tối thiểu để rút tiền là 50,000 VNĐ');
        }
        
        return view('wallet.withdraw', compact('wallet'));
    }

    public function processWithdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50000|max:10000000',
            'bank_name' => 'required|string|max:100',
            'bank_account' => 'required|string|max:50',
            'account_holder' => 'required|string|max:100'
        ]);

        try {
            $transaction = $this->walletService->withdraw(
                auth()->id(),
                $validated['amount'],
                'bank_transfer',
                [
                    'bank_name' => $validated['bank_name'],
                    'bank_account' => $validated['bank_account'],
                    'account_holder' => $validated['account_holder']
                ]
            );

            return redirect()->route('wallet.transactions')
                ->with('success', 'Yêu cầu rút tiền đã được gửi. Chúng tôi sẽ xử lý trong 24h.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function transfer()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->id());
        return view('wallet.transfer', compact('wallet'));
    }

    public function processTransfer(Request $request)
    {
        $validated = $request->validate([
            'from_type' => 'required|in:real,virtual,bonus',
            'to_type' => 'required|in:real,virtual,bonus',
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validated['from_type'] === $validated['to_type']) {
            return back()->with('error', 'Không thể chuyển cùng loại ví');
        }

        try {
            $this->walletService->transfer(
                auth()->id(),
                $validated['from_type'],
                $validated['to_type'],
                $validated['amount'],
                $validated['description']
            );

            return redirect()->route('wallet.index')
                ->with('success', 'Chuyển ví thành công!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    private function redirectToPaymentGateway($transaction, $gateway)
    {
        switch ($gateway) {
            case 'vnpay':
                return redirect()->route('payment.vnpay', ['transaction' => $transaction->transaction_id]);
            case 'momo':
                return redirect()->route('payment.momo', ['transaction' => $transaction->transaction_id]);
            case 'bank_transfer':
                return redirect()->route('payment.bank-transfer', ['transaction' => $transaction->transaction_id]);
            default:
                throw new \Exception('Invalid payment gateway');
        }
    }
}
```

### Bước 5: Tạo Wallet Views
```blade
{{-- resources/views/wallet/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Ví Điện Tử</h1>

    {{-- Balance Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">Số dư thật</h5>
                    <h3 class="text-success">{{ number_format($wallet->real_balance) }}đ</h3>
                    <small class="text-muted">Có thể rút ra</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">Số dư ảo</h5>
                    <h3 class="text-info">{{ number_format($wallet->virtual_balance) }}đ</h3>
                    <small class="text-muted">Chỉ để test</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">Số dư thưởng</h5>
                    <h3 class="text-warning">{{ number_format($wallet->bonus_balance) }}đ</h3>
                    <small class="text-muted">Khuyến mãi</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-secondary">Đang đóng băng</h5>
                    <h3 class="text-secondary">{{ number_format($wallet->frozen_balance) }}đ</h3>
                    <small class="text-muted">Đang xử lý</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Thao tác nhanh</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('wallet.deposit') }}" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> Nạp tiền
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('wallet.withdraw') }}" 
                               class="btn btn-primary w-100 {{ $wallet->real_balance < 50000 ? 'disabled' : '' }}">
                                <i class="fas fa-minus"></i> Rút tiền
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('wallet.transfer') }}" class="btn btn-info w-100">
                                <i class="fas fa-exchange-alt"></i> Chuyển ví
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('wallet.transactions') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-history"></i> Lịch sử
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>Giao dịch gần đây</h5>
                    <a href="{{ route('wallet.transactions') }}" class="btn btn-sm btn-outline-primary">
                        Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($recentTransactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Loại</th>
                                        <th>Số tiền</th>
                                        <th>Ví</th>
                                        <th>Trạng thái</th>
                                        <th>Mô tả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $this->getTransactionTypeColor($transaction->type) }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td class="text-{{ $transaction->amount > 0 ? 'success' : 'danger' }}">
                                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }}đ
                                        </td>
                                        <td>{{ ucfirst($transaction->balance_type) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $this->getStatusColor($transaction->status) }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">Chưa có giao dịch nào</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@php
function getTransactionTypeColor($type) {
    return [
        'deposit' => 'success',
        'withdrawal' => 'primary', 
        'transfer' => 'info',
        'campaign' => 'warning',
        'bonus' => 'success',
        'refund' => 'secondary'
    ][$type] ?? 'secondary';
}

function getStatusColor($status) {
    return [
        'completed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger',
        'cancelled' => 'secondary'
    ][$status] ?? 'secondary';
}
@endphp
@endsection
```

### Bước 6: Tạo Observer để tự động tạo Wallet
```php
// app/Observers/UserObserver.php
class UserObserver
{
    public function created(User $user)
    {
        // Auto create wallet when user is created
        app(WalletService::class)->createWallet($user->id);
    }
}

// app/Providers/AppServiceProvider.php
public function boot()
{
    User::observe(UserObserver::class);
}
```

## Testing

### Unit Tests
```php
// tests/Unit/WalletServiceTest.php
class WalletServiceTest extends TestCase
{
    public function test_can_create_wallet()
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->createWallet($user->id);
        
        $this->assertEquals($user->id, $wallet->user_id);
        $this->assertEquals(0, $wallet->real_balance);
        $this->assertEquals(1000000, $wallet->virtual_balance);
    }

    public function test_can_deposit_money()
    {
        $user = User::factory()->create();
        $service = app(WalletService::class);
        
        $transaction = $service->deposit($user->id, 100000, 'vnpay');
        
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals(100000, $transaction->amount);
    }
}
```

## Security Considerations

1. **Database Transactions**: Sử dụng DB transactions cho tất cả operations
2. **Balance Validation**: Luôn kiểm tra số dư trước khi thực hiện giao dịch  
3. **Audit Trail**: Ghi log đầy đủ mọi thay đổi số dư
4. **Rate Limiting**: Giới hạn số giao dịch trong một khoảng thời gian
5. **Two-Factor Authentication**: Yêu cầu 2FA cho rút tiền lớn

## Monitoring & Alerts

1. **Balance Monitoring**: Theo dõi các thay đổi bất thường về số dư
2. **Failed Transactions**: Alert khi có quá nhiều giao dịch thất bại
3. **Large Transactions**: Thông báo cho admin khi có giao dịch lớn
4. **Daily Reconciliation**: Đối soát số dư hàng ngày 
