<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

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
