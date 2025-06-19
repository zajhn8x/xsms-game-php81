<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasFactory;

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

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    // Generate unique transaction ID
    public static function generateTransactionId(): string
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }
}
