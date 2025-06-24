<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'name', 'description', 'campaign_type',
        'start_date', 'end_date', 'days',
        'initial_balance', 'current_balance', 'target_profit',
        'daily_bet_limit', 'max_loss_per_day', 'total_loss_limit',
        'auto_stop_loss', 'auto_take_profit', 'stop_loss_amount', 'take_profit_amount',
        'betting_strategy', 'strategy_config',
        'is_public', 'status', 'notes',
        'total_bet_amount', 'total_win_amount', 'total_bet_count', 'win_bet_count', 'win_rate',
        'last_bet_at', 'bet_type', 'last_updated'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_updated' => 'date',
        'last_bet_at' => 'datetime',
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'target_profit' => 'decimal:2',
        'daily_bet_limit' => 'decimal:2',
        'max_loss_per_day' => 'decimal:2',
        'total_loss_limit' => 'decimal:2',
        'stop_loss_amount' => 'decimal:2',
        'take_profit_amount' => 'decimal:2',
        'total_bet_amount' => 'decimal:2',
        'total_win_amount' => 'decimal:2',
        'win_rate' => 'decimal:2',
        'auto_stop_loss' => 'boolean',
        'auto_take_profit' => 'boolean',
        'is_public' => 'boolean',
        'strategy_config' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(CampaignBet::class);
    }

    public function autoRules(): HasMany
    {
        return $this->hasMany(CampaignAutoRule::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(CampaignShare::class);
    }

    public function getWinRateAttribute(): float
    {
        $totalBets = $this->bets()->count();
        if ($totalBets === 0) return 0;

        $winBets = $this->bets()->where('is_win', true)->count();
        return round(($winBets / $totalBets) * 100, 2);
    }

    public function getProfitAttribute(): float
    {
        return $this->current_balance - $this->initial_balance;
    }

    public function getProfitPercentageAttribute(): float
    {
        if ($this->initial_balance == 0) return 0;
        return round(($this->profit / $this->initial_balance) * 100, 2);
    }

    public function getTotalBetAmountAttribute(): float
    {
        return $this->bets()->sum('amount');
    }

    public function getTotalWinAmountAttribute(): float
    {
        return $this->bets()->where('is_win', true)->sum('win_amount');
    }

    public function getDaysRunningAttribute(): int
    {
        if ($this->status === 'pending') return 0;

        $startDate = $this->start_date;
        $endDate = $this->status === 'completed' ? $this->end_date : now();

        return $startDate->diffInDays($endDate) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['active', 'running']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }
}
