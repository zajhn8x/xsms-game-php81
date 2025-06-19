<?php

namespace App\Http\Controllers;

use App\Models\FormulaHeatmapInsight;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Queries\HeatmapInsightQueryService;
use Illuminate\Support\Facades\Log;

class HeatmapAnalyticController extends Controller
{
    protected $insightQueryService;

    public function __construct(HeatmapInsightQueryService $insightQueryService)
    {
        $this->insightQueryService = $insightQueryService;
    }

    public function index(Request $request, $date = null)
    {
        $type = $request->get('type','');

        // Nếu không có date truyền vào thì lấy date mới nhất từ FormulaHeatmapInsight
        if (!$date) {
            $latestInsight = FormulaHeatmapInsight::latest('date')->first();
            $date = $latestInsight ? $latestInsight->date : now()->toDateString();
        }

        $limit = 50;
        $insights = $this->insightQueryService->getTopInsights($date, $type, $limit);

        // Chuẩn bị dữ liệu cho view - xử lý đơn giản
        foreach ($insights as $insight) {
            $this->processInsight($insight);
        }

        $types = $this->insightQueryService->getTypes();

        // Chỉ lấy 10 ngày xung quanh ngày hiện tại
        $currentDate = Carbon::parse($date);
        $availableDates = collect();
        for ($i = -5; $i <= 5; $i++) {
            $checkDate = $currentDate->copy()->addDays($i);
            // Kiểm tra xem ngày này có dữ liệu không
            if (FormulaHeatmapInsight::whereDate('date', $checkDate->toDateString())->exists()) {
                $availableDates->push($checkDate->toDateString());
            }
        }

        return view('heatmap.analytic', [
            'insights' => $insights,
            'types' => $types,
            'filters' => $request->all(),
            'currentDate' => $currentDate,
            'availableDates' => $availableDates
        ]);
    }

    private function processInsight($insight)
    {
        $extra = $insight->extra;
        $hit = $extra['hit'] ?? null;
        unset($extra['hit']);

        // Xử lý predicted values
        $predictedValues = $extra['predicted_values_by_position'] ?? [];
        $processedPredicted = [];
        foreach ($predictedValues as $position => $val) {
            $processedPredicted[$position] = $val;
        }

        // Xử lý dữ liệu theo type
        $processedExtra = [];
        switch ($insight->type) {
            case 'long_run':
                $processedExtra = [
                    'streak_length' => $extra['streak_length'] ?? null,
                    'value' => $extra['value'] ?? null,
                    'predicted_values_by_position' => $processedPredicted,
                ];
                break;
            case 'long_run_stop':
                $processedExtra = [
                    'streak_length' => $extra['streak_length'] ?? null,
                    'day_stop' => $extra['day_stop'] ?? null,
                    'value' => $extra['value'] ?? null,
                    'predicted_values_by_position' => $processedPredicted,
                ];
                break;
            case 'rebound_after_long_run':
                $processedExtra = [
                    'streak_length' => $extra['streak_length'] ?? null,
                    'stop_days' => $extra['stop_days'] ?? null,
                    'step' => $extra['step'] ?? null,
                    'step_1' => $extra['step_1'] ?? null,
                    'step_2' => $extra['step_2'] ?? null,
                    'value' => $extra['value'] ?? null,
                    'rebound_success' => $extra['rebound_success'] ?? null,
                    'predicted_values_by_position' => $processedPredicted,
                ];
                break;
        }

        $insight->setAttribute('processed_extra', $processedExtra);
        $insight->setAttribute('hit', $hit);
        $insight->setAttribute('link', route('caulo.timeline', ['id' => $insight->formula_id]));
    }
}