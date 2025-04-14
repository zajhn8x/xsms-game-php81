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

        return view('heatmap.index', [
            'heatmapData' => $heatmapData,
            'startDate' => $endDate->copy()->subDays(19),
            'endDate' => $endDate
        ]);
    }
}