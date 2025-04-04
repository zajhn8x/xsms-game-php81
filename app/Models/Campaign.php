<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'days',
        'initial_balance',
        'current_balance',
        'bet_type',
        'status',
        'last_updated'
    ];

    protected $casts = [
        'start_date' => 'date',
        'last_updated' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bets()
    {
        return $this->hasMany(CampaignBet::class);
    }

    public function getWinRateAttribute()
    {
        $totalBets = $this->bets()->count();
        if ($totalBets === 0) return 0;

        $winBets = $this->bets()->where('is_win', true)->count();
        return ($winBets / $totalBets) * 100;
    }

    public function getProfitAttribute()
    {
        return $this->current_balance - $this->initial_balance;
    }

    public function getProfitRateAttribute()
    {
        if ($this->initial_balance === 0) return 0;
        return ($this->profit / $this->initial_balance) * 100;
    }

    public function getTotalBetAmountAttribute()
    {
        return $this->bets()->sum('amount');
    }

    public function getTotalWinAmountAttribute()
    {
        return $this->bets()->where('is_win', true)->sum('win_amount');
    }
}
