<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoricalCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description',
        'test_start_date', 'test_end_date',
        'data_start_date', 'data_end_date',
        'initial_balance', 'final_balance',
        'betting_strategy', 'strategy_config', 'status'
    ];

    protected $casts = [
        'test_start_date' => 'date',
        'test_end_date' => 'date',
        'data_start_date' => 'date',
        'data_end_date' => 'date',
        'initial_balance' => 'decimal:2',
        'final_balance' => 'decimal:2',
        'strategy_config' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(HistoricalBet::class);
    }

    public function getProfitAttribute(): float
    {
        return $this->final_balance - $this->initial_balance;
    }

    public function getProfitPercentageAttribute(): float
    {
        return $this->initial_balance > 0
            ? round(($this->profit / $this->initial_balance) * 100, 2)
            : 0;
    }

    public function getWinRateAttribute(): float
    {
        $totalBets = $this->bets()->count();
        if ($totalBets === 0) return 0;

        $winBets = $this->bets()->where('is_win', true)->count();
        return round(($winBets / $totalBets) * 100, 2);
    }

    public function getDurationAttribute(): int
    {
        return $this->test_start_date->diffInDays($this->test_end_date) + 1;
    }
}
