<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Get total available balance
    public function getAvailableBalanceAttribute(): float
    {
        return $this->real_balance + $this->virtual_balance + $this->bonus_balance;
    }

    // Get total balance including frozen
    public function getTotalBalanceAttribute(): float
    {
        return $this->available_balance + $this->frozen_balance;
    }

    // Get usable balance for betting (real + bonus)
    public function getUsableBalanceAttribute(): float
    {
        return $this->real_balance + $this->bonus_balance;
    }
}
