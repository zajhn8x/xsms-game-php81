<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryResultIndex extends Model
{
    use HasFactory;

    protected $table = 'lottery_results_index';

    protected $fillable = ['draw_date', 'position', 'value'];

    public $timestamps = true;
}
