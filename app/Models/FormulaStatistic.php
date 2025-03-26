<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormulaStatistic extends Model
{
    use HasFactory;

    protected $table = 'formula_statistics';

    protected $fillable = [
        'formula_id',
        'year',
        'quarter',
        'frequency',
        'win_cycle',
        'probability',
        'streak_3',
        'streak_4',
        'streak_5',
        'streak_6',
        'streak_more_6',
        'prev_streak',
        'last_streak'
    ];

    public $timestamps = true;

    /**
     * Liên kết với công thức cầu lô
     */
    public function formula()
    {
        return $this->belongsTo(LotteryFormula::class, 'formula_id');
    }
}
