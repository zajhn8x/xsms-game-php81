<?php

namespace App\Services;

use App\Models\LotteryFormula;
use App\Models\FormulaHit;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FormulaHitService
{
    protected $resultIndexService;

    public function __construct(LotteryResultService $resultService, LotteryIndexResultsService $resultIndexService)
    {
        $this->resultService = $resultService;
        $this->resultIndexService = $resultIndexService;
    }

    /**
     * Lấy dữ liệu heatmap cho 100 ID trong 20 ngày
     * @param string $endDate Ngày kết thúc format Y-m-d
     * @return array Mảng dữ liệu heatmap theo ngày
     */
    public function getHeatMap($endDate = null) 
    {
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::today();
        $startDate = $endDate->copy()->subDays(19); // Lấy 20 ngày tính cả ngày hiện tại
        
        $heatmapData = [];
        $currentDate = $endDate->copy();

        // Lặp qua 20 ngày
        while ($currentDate >= $startDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = [];

            // Query cho từng mức streak
            for ($streak = 7; $streak >= 2; $streak--) {
                $hits = FormulaHit::select([
                        'formula_hit.cau_lo_id',
                        'meta.formula_name',
                        DB::raw('GROUP_CONCAT(formula_hit.ngay ORDER BY formula_hit.ngay ASC) as ngay_trung'),
                        DB::raw('COUNT(DISTINCT formula_hit.ngay) as streak_count')
                    ])
                    ->join('lottery_formula_meta as meta', 'formula_hit.cau_lo_id', '=', 'meta.id')
                    ->where('formula_hit.ngay', '<=', $dateStr)
                    ->whereExists(function ($query) use ($dateStr, $streak) {
                        $query->from('formula_hit as h2')
                            ->whereColumn('h2.cau_lo_id', 'formula_hit.cau_lo_id')
                            ->whereRaw('DATEDIFF(?, h2.ngay) BETWEEN 0 AND ?', [$dateStr, $streak - 1]);
                    })
                    ->groupBy(['formula_hit.cau_lo_id', 'meta.formula_name'])
                    ->having('streak_count', '=', $streak)
                    ->get();

                // Thêm các hits vào dayData
                foreach ($hits as $hit) {
                    $value = substr($hit->formula_name, -2); // Lấy 2 số cuối của formula_name
                    $dayData[$hit->cau_lo_id] = [
                        'id' => 'CL_' . $value,
                        'streak' => $hit->streak_count,
                        'value' => $value,
                        'hit' => $value,
                        'status' => $hit->streak_count >= 7 ? 4 : ($hit->streak_count == 6 ? 2 : 0)
                    ];
                }
            }

            // Đảm bảo có đủ 100 ô cho mỗi ngày
            for ($i = 0; $i < 100; $i++) {
                $id = str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!isset($dayData[$i])) {
                    $dayData[$i] = [
                        'id' => 'CL_' . $id,
                        'streak' => 0,
                        'value' => $id,
                        'hit' => null,
                        'status' => 0
                    ];
                }
            }

            ksort($dayData);
            $heatmapData[$dateStr] = array_values($dayData);
            $currentDate->subDay();
        }

        return $heatmapData;
    }

    /**
     * Lấy danh sách công thức có streak liên tiếp
     */
    public function getStreakFormulas($fromDate, $streak = 2, $limit = 3)
    {
        return FormulaHit::withStreak($fromDate, $streak)
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy dữ liệu timeline cho một công thức
     */
    /**
     * Lấy dữ liệu streak cho biểu đồ
     */
    public function getStreakData(array $hits, array $dateRange): array
    {
        $streakData = [];
        $prevStreak = 0;

        foreach ($dateRange as $date) {
            $hit = $hits[$date] ?? null;
            $streak = 0;

            if ($hit) {
                $streak = isset($streakData[array_key_last($streakData)])
                    ? $streakData[array_key_last($streakData)] + 1
                    : 1;
                if ($streak > 5) $streak = 5;
            } else if ($prevStreak > 0) {
                $streak = -1; // Đánh dấu gián đoạn
            }

            if ($streak != 0) {
                $streakData[$date] = $streak;
            }
            $prevStreak = $streak > 0 ? $streak : 0;
        }

        return $streakData;
    }

    public function getTimelineData(LotteryFormula $cauLo, Carbon $startDate, int $daysBack = 30): array
    {
        // Get date range
        $endDate = $startDate->copy()->subDays($daysBack);
        $dateRange = [];
        $currentDate = $startDate->copy();

        while ($currentDate >= $endDate) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->subDay();
        }

        // Get hits for this cau lo in date range
        $hits = FormulaHit::where('cau_lo_id', $cauLo->id)
            ->whereBetween('ngay', [$endDate, $startDate])
            ->get()
            ->keyBy('ngay');

        // Get lottery results for the date range
        $results = LotteryResult::whereBetween('draw_date', [$endDate, $startDate])
            ->get()
            ->keyBy('draw_date');

        // Get lottery results index for data range
        $resultIndexs = $this->resultIndexService->getDrawDates(
            $cauLo->formula->positions,
            $endDate->format('Y-m-d'),
            $startDate->format('Y-m-d')
        );

        // Xử lý Pair $resultIndexs tạo các cặp số ghép
        foreach ($resultIndexs as $date => &$values) {
            $pairs = [];
            if (count($values) > 1) {
                for ($i = 0; $i < count($values); $i++) {
                    for ($j = $i + 1; $j < count($values); $j++) {
                        $pairs[] = "{$values[$i]}{$values[$j]}";
                        $pairs[] = "{$values[$j]}{$values[$i]}";
                    }
                }
            }
            $resultIndexs[$date] = [
                'values' => $values,
                'pairs' => array_values(array_unique($pairs))
            ];
        }

        // Get meta information
        $meta = [
            'formula_name' => $cauLo->formula->name ?? '',
            'formula_structure' => $cauLo->formula->structure ?? '',
            'total_hits' => $hits->count(),
            'hit_rate' => count($dateRange) > 0
                ? ($hits->count() / count($dateRange)) * 100
                : 0
        ];

        return [
            'meta' => $meta,
            'dateRange' => $dateRange,
            'hits' => $hits,
            'results' => $results,
            'resultIndexs' => $resultIndexs
        ];
    }

    /**
     * Tìm các công thức trúng liên tiếp
     */
    public function findConsecutiveHits($date, $days = 3)
    {
        return FormulaHit::withConsecutiveDays($days, $date)->get();
    }
}
