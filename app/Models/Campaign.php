
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
}
