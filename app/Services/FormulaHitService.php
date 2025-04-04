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