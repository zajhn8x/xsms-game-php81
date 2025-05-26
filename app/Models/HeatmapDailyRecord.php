<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapDailyRecord extends Model
{
    protected $fillable = [
        'date',
        'data'
    ];

    protected $casts = [
        'date' => 'date',
        'data' => 'array'
    ];
}
