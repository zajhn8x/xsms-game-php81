<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryBet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bet_date',
        'lo_number',
        'amount',
        'is_win',
        'win_amount'
    ];

    protected $casts = [
        'bet_date' => 'date',
        'is_win' => 'boolean',
        'amount' => 'decimal:2',
        'win_amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
