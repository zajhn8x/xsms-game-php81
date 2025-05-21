<?php

namespace App\Http\Controllers;

use App\Services\FormulaHitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        $cacheKey = 'heatmap_data_' . $endDate->format('Y-m-d');
        $heatmapData = cache()->remember($cacheKey, now()->addDay(), function () use ($endDate) {
            return $this->formulaHitService->getHeatMap($endDate);
        });

        // Chuẩn bị dữ liệu cho view
        $processedData = [];
        foreach ($heatmapData as $date => $dayData) {
            $processedData[$date] = [
                'data' => array_map(function($cell) {
                    $extra = $cell['extra'] ?? [];
                    unset($extra['hit']); // Loại bỏ hit khỏi extra
                    
                    return [
                        'id' => $cell['id'],
                        'streak' => $cell['streak'],
                        'hit' => $cell['hit'] ?? null,
                        'extra' => $extra
                    ];
                }, $dayData['data'])
            ];
        }

        return view('heatmap.index', [
            'heatmapData' => $processedData,
            'startDate' => $endDate->copy()->subDays(19),
            'endDate' => $endDate
        ]);
    }
}
