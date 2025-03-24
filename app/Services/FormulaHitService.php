<?php
namespace App\Services;

use App\Models\LotteryFormula;
use App\Models\FormulaHit;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FormulaHitService
{
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
            'results' => $results
        ];
    }
}
