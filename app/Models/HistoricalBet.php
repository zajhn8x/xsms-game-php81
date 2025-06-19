<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalBet extends Model
{
    use HasFactory;

    protected $fillable = [
        'historical_campaign_id', 'bet_date', 'lo_number',
        'amount', 'win_amount', 'is_win',
        'balance_before', 'balance_after', 'notes'
    ];

    protected $casts = [
        'bet_date' => 'date',
        'amount' => 'decimal:2',
        'win_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_win' => 'boolean'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(HistoricalCampaign::class, 'historical_campaign_id');
    }

    public function getProfitAttribute(): float
    {
        return $this->is_win ? $this->win_amount - $this->amount : -$this->amount;
    }
}
