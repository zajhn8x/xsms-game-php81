<?php

namespace App\Http\Controllers;

use App\Models\FormulaHeatmapInsight;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HeatmapAnalyticController extends Controller
{
    public function index(Request $request, $date = null)
    {
        $query = FormulaHeatmapInsight::with('formula');

        // Filter theo type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter theo ngày
        if ($date) {
            $query->whereDate('date', $date);
        } else {
            $query->whereDate('date', Carbon::today());
        }

        // Filter theo score
        if ($request->has('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        // Filter theo streak length
        if ($request->has('min_streak')) {
            $query->whereRaw("JSON_EXTRACT(extra, '$.streak_length') >= ?", [$request->min_streak]);
        }

        // Filter theo hit status
        if ($request->has('hit_status')) {
            $query->whereRaw("JSON_EXTRACT(extra, '$.hit') = ?", [$request->hit_status === 'true']);
        }

        $insights = $query->orderBy('score', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Chuẩn bị dữ liệu cho view
        $insights->getCollection()->transform(function ($insight) {
            $extra = $insight->extra;
            $hit = $extra['hit'] ?? null;
            unset($extra['hit']); // Loại bỏ hit khỏi extra
            
            // Lấy predicted_values_by_position
            $predictedValues = $extra['predicted_values_by_position'] ?? [];
            $processedPredicted = [];
            foreach ($predictedValues as $position => $val) {
                $processedPredicted[$position] = $val;
            }
            // Chuẩn bị dữ liệu extra theo type
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
                        'step_1' => $extra['step_1'] ?? null,
                        'step_2' => $extra['step_2'] ?? null,
                        'value' => $extra['value'] ?? null,
                        'rebound_success' => $extra['rebound_success'] ?? null,
                        'predicted_values_by_position' => $processedPredicted,
                    ];
                    break;
            }
            $insight->processed_extra = $processedExtra;
            $insight->hit = $hit;
            return $insight;
        });

        $types = FormulaHeatmapInsight::select('type')
            ->distinct()
            ->pluck('type');

        // Lấy danh sách các ngày có dữ liệu
        $availableDates = FormulaHeatmapInsight::select('date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->map(function ($date) {
                return [
                    'value' => $date->format('Y-m-d'),
                    'label' => $date->format('d/m/Y')
                ];
            });

        return view('heatmap.analytic', [
            'insights' => $insights,
            'types' => $types,
            'filters' => $request->all(),
            'currentDate' => $date ? Carbon::parse($date) : Carbon::today(),
            'availableDates' => $availableDates
        ]);
    }
} 