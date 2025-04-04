<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignBet extends Model
{
    protected $fillable = [
        'campaign_id',
        'lo_number',
        'points',
        'amount',
        'win_amount',
        'bet_date',
        'is_win',
        'status'
    ];

    protected $casts = [
        'bet_date' => 'date',
        'is_win' => 'boolean'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
