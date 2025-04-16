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
        'so_trung',
        'status'
    ];

    protected $casts = [
        'ngay' => 'date'
    ];

    protected $dates = ['ngay'];

    public function getNgayAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function getFormula()
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
            ->whereExists(function ($query) use ($days) {
                $query->from('formula_hit as h2')
                    ->whereColumn('h2.cau_lo_id', 'formula_hit.cau_lo_id')
                    ->whereRaw('DATEDIFF(formula_hit.ngay, h2.ngay) BETWEEN 0 AND ' . ($days - 1));
            })
            ->groupBy(['formula_hit.cau_lo_id', 'meta.formula_name', 'meta.formula_structure', 'meta.combination_type'])
            ->having(static::raw('COUNT(DISTINCT formula_hit.ngay)'), '=', $days);
    }

    public function scopeWithStreak($query, $fromDate, $streak = 2)
    {

        $query->select(['formula_hit.cau_lo_id', static::raw('MAX(formula_hit.ngay) as ngay_moi_nhat')]);

        for ($i = 1; $i < $streak; $i++) {
            $query->join("formula_hit as t{$i}", function ($join) use ($i) {
                $join->on('formula_hit.cau_lo_id', '=', "t{$i}.cau_lo_id")
                    ->whereRaw("t{$i}.ngay = DATE_SUB(formula_hit.ngay, INTERVAL {$i} DAY)");
            });
        }

        $query->where('formula_hit.ngay', '=', $fromDate)
            ->groupBy('cau_lo_id')
            ->orderByDesc('ngay_moi_nhat');
    }

    /**
     * Lấy thông tin hit và streak theo danh sách cầu lô và ngày tương ứng
     *
     * @param array $cauLoIds
     * @param array $dates
     * @return array
     */
    public function getHitDataWithStreak(array $cauLoIds, array $dates): array
    {
        $results = [];

        foreach ($cauLoIds as $index => $cauLoId) {
            $date = $dates[$index] ?? null;

            if (!$date) continue;

            $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                ->where('ngay', $date)
                ->first();

            $streak = $this->calculateStreak($cauLoId, $date);

            $results[$date][$cauLoId] = [
                'cau_lo_id' => $cauLoId,
                'ngay' => $date,
                'so_trung' => $hit->so_trung ?? null,
                'status' => $hit->status ?? 0,
                'streak' => $streak
            ];
        }

        return $results;
    }

    /**
     * Tính streak của 1 cầu lô tới ngày $date
     *
     * @param string|int $cauLoId
     * @param string $date (Y-m-d)
     * @return int
     */
    private function calculateStreak($cauLoId, $date): int
    {
        $streak = 0;
        $currentDate = Carbon::parse($date);

        while (true) {
            $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                ->where('ngay', $currentDate->toDateString())
                ->exists();

            if ($hit) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
