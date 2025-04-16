<?php

namespace App\Http\Controllers;

use App\Services\FormulaHitService;
use Carbon\Carbon;

class HeatmapController extends Controller
{
    protected $formulaHitService;

    public function __construct(FormulaHitService $formulaHitService)
    {
        $this->formulaHitService = $formulaHitService;
    }

    /**
     * Hiển thị trang heatmap của các streak trong 20 ngày gần nhất
     */
    public function index()
    {
        $endDate = Carbon::today();
        $heatmapData = $this->formulaHitService->getHeatMap($endDate);
        dump($heatmapData);
//dump($heatmapData = $this->formulaHitService->getHitDataWithStreak(['31','247','324'],['2025-04-01','2025-04-02','2025-04-03']));
//        ($this->formulaHitService->getStreakFormulas('2025-04-01',2,50));
//        dump($this->formulaHitService->findConsecutiveHits('2025-04-01',3));
        return view('heatmap.index', [
            'heatmapData' => $heatmapData,
            'startDate' => $endDate->copy()->subDays(19),
            'endDate' => $endDate
        ]);
    }
}
