
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
        $startDate = $endDate->copy()->subDays(20);
        
        // Lấy danh sách các công thức và streak của chúng trong 20 ngày
        $timelineData = [];
        $currentDate = $endDate->copy();
        
        while($currentDate >= $startDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $streakData = $this->formulaHitService->findConsecutiveHits($dateStr, 2);
            $timelineData[$dateStr] = $streakData;
            $currentDate->subDay();
        }

        return view('heatmap.index', [
            'timelineData' => $timelineData,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
