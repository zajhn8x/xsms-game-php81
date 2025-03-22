<?php

namespace App\Services;

use App\Models\LotteryCauMeta;
use App\Models\LotteryCauLo;
use Carbon\Carbon;

class LotteryCauService
{
    public function getActiveCaus()
    {
        return LotteryCauMeta::with(['cauLos' => function($query) {
            $query->where('draw_date', '>=', Carbon::now()->subDays(30));
        }])->get();
    }

    public function analyzeCauPerformance($formulaId, $days = 30)
    {
        $cauLos = LotteryCauLo::where('formula_id', $formulaId)
            ->where('draw_date', '>=', Carbon::now()->subDays($days))
            ->get();

        return [
            'total_predictions' => $cauLos->count(),
            'successful_predictions' => $cauLos->where('occurrence', '>', 0)->count(),
            'success_rate' => $cauLos->count() > 0
                ? ($cauLos->where('occurrence', '>', 0)->count() / $cauLos->count()) * 100
                : 0
        ];
    }

    public function predictForTomorrow($formulaId)
    {
        $formula = LotteryCauMeta::findOrFail($formulaId);
        $latestPredictions = LotteryCauLo::where('formula_id', $formulaId)
            ->orderBy('draw_date', 'desc')
            ->take(7)
            ->get();

        // Implement your prediction logic here based on historical data
        return [
            'formula' => $formula->name,
            'predicted_numbers' => [], // Add your prediction algorithm
            'confidence_score' => 0 // Add your confidence calculation
        ];
    }
}
