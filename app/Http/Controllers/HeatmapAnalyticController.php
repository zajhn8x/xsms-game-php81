<?php

namespace App\Http\Controllers;

use App\Models\FormulaHeatmapInsight;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Queries\HeatmapInsightQueryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        $date = $date ?? now()->toDateString();
        $limit = 50;
        $insights = $this->insightQueryService->getTopInsights($date, $type, $limit);

        // Log toàn bộ dữ liệu để kiểm tra
        Log::info('Heatmap Analytic Data', [
            'date' => $date,
            'type' => $type,
            'total_insights' => $insights instanceof LengthAwarePaginator ? $insights->total() :
                              ($insights instanceof Collection ? $insights->count() : count($insights))
        ]);

        // Chuẩn bị dữ liệu cho view
        if ($insights instanceof Collection || $insights instanceof LengthAwarePaginator) {
            $insights->each(function ($insight) {
                $this->processInsight($insight);
            });
        } else {
            foreach ($insights as $insight) {
                $this->processInsight($insight);
            }
        }

        $types = $this->insightQueryService->getTypes();
        $availableDates = $this->insightQueryService->getAvailableDates();

        return view('heatmap.analytic', [
            'insights' => $insights,
            'types' => $types,
            'filters' => $request->all(),
            'currentDate' => $date ? Carbon::parse($date) : Carbon::today(),
            'availableDates' => $availableDates
        ]);
    }

    private function processInsight($insight)
    {
        $extra = $insight->extra;

        // Log chi tiết từng insight
        Log::info('Insight Detail', [
            'id' => $insight->id,
            'formula_id' => $insight->formula_id,
            'date' => $insight->date,
            'type' => $insight->type,
            'score' => $insight->score,
            'raw_extra' => $extra
        ]);

        $hit = $extra['hit'] ?? null;
        unset($extra['hit']);

        // Xử lý predicted values
        $predictedValues = $extra['predicted_values_by_position'] ?? [];
        $processedPredicted = [];
        foreach ($predictedValues as $position => $val) {
            $processedPredicted[$position] = $val;
        }

        // Giữ nguyên toàn bộ extra, chỉ thêm processed_extra để hiển thị
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

        // Thêm cả raw_extra để debug
        $insight->setAttribute('raw_extra', $extra);
        $insight->setAttribute('processed_extra', $processedExtra);
        $insight->setAttribute('hit', $hit);
        $insight->setAttribute('link', route('caulo.timeline', ['id' => $insight->formula_id]));
    }
}