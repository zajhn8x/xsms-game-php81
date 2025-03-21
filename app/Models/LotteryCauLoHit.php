<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryCauLoHit extends Model
{
    public $timestamps = false;

    protected $table = 'lottery_cau_lo_hit';

    protected $fillable = [
        'cau_lo_id',
        'ngay',
        'so_trung'
    ];

    protected $casts = [
        'ngay' => 'date'
    ];

    public function cauLo()
    {
        return $this->belongsTo(LotteryCauLo::class, 'cau_lo_id');
    }

    public static function getWinningStreaks($date)
    {
        return DB::table('lottery_cau_lo_hit as h1')
            ->join('lottery_cau_lo_hit as h2', function ($join) {
                $join->on('h1.cau_lo_id', '=', 'h2.cau_lo_id')
                    ->whereRaw('h1.ngay = DATE_ADD(h2.ngay, INTERVAL 1 DAY)');
            })
            ->join('lottery_cau_lo_hit as h3', function ($join) {
                $join->on('h1.cau_lo_id', '=', 'h3.cau_lo_id')
                    ->whereRaw('h1.ngay = DATE_ADD(h3.ngay, INTERVAL 2 DAY)');
            })
            ->where('h1.ngay', '<=', $date)
            ->groupBy('h1.cau_lo_id')
            ->select('h1.cau_lo_id', DB::raw('GROUP_CONCAT(h1.ngay ORDER BY h1.ngay ASC) AS ngay_trung'))
            ->get();
    }
}
