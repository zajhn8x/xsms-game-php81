
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    protected $fillable = [
        'draw_date',
        'prizes',
        'lo_array'
    ];

    protected $casts = [
        'draw_date' => 'datetime',
        'prizes' => 'array',
        'lo_array' => 'array'
    ];
}
