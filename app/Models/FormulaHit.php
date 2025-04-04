
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FormulaHit extends Model
{
    public $timestamps = false;

    protected $table = 'formula_hit';

    protected $fillable = [
        'cau_lo_id',
        'ngay',
        'so_trung'
    ];

    protected $casts = [
        'ngay' => 'date'
    ];

    protected $dates = ['ngay'];

    public function getNgayAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function cauLo()
    {
        return $this->belongsTo(LotteryFormula::class, 'cau_lo_id');
    }

    public function scopeWithConsecutiveDays($query, $days, $date)
    {
        $query->select([
            'formula_hit.cau_lo_id',
            'meta.formula_name',
            'meta.formula_structure', 
            'meta.combination_type',
            static::raw('GROUP_CONCAT(formula_hit.ngay ORDER BY formula_hit.ngay ASC) as ngay_trung')
        ])
        ->join('lottery_formula_meta as meta', 'formula_hit.cau_lo_id', '=', 'meta.id')
        ->where('formula_hit.ngay', '<=', $date)
        ->whereExists(function($query) use ($days) {
            $query->from('formula_hit as h2')
                ->whereColumn('h2.cau_lo_id', 'formula_hit.cau_lo_id')
                ->whereRaw('DATEDIFF(formula_hit.ngay, h2.ngay) BETWEEN 0 AND '.($days-1));
        })
        ->groupBy(['formula_hit.cau_lo_id', 'meta.formula_name', 'meta.formula_structure', 'meta.combination_type'])
        ->having(static::raw('COUNT(DISTINCT formula_hit.ngay)'), '=', $days);
    }

    public function scopeWithStreak($query, $fromDate, $streak = 2)
    {
        $query->select(['formula_hit.cau_lo_id', static::raw('MAX(formula_hit.ngay) as ngay_moi_nhat')]);
        
        for ($i = 1; $i < $streak; $i++) {
            $query->join("formula_hit as t{$i}", function($join) use ($i) {
                $join->on('formula_hit.cau_lo_id', '=', "t{$i}.cau_lo_id")
                    ->whereRaw("t{$i}.ngay = DATE_SUB(formula_hit.ngay, INTERVAL {$i} DAY)");
            });
        }

        $query->where('formula_hit.ngay', '>=', $fromDate)
            ->groupBy('cau_lo_id')
            ->orderByDesc('ngay_moi_nhat');
    }
}
