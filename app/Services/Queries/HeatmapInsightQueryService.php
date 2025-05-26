<?php

namespace App\Services\Queries;

use App\Models\FormulaHeatmapInsight;

class HeatmapInsightQueryService
{
    /**
     * Lấy danh sách insight tốt nhất theo điều kiện
     */
    public function getTopInsights(string $date, string $strategy, int $limit)
    {
        $query = FormulaHeatmapInsight::query();
        if (!empty($strategy)) {
            $query->where('type', $strategy);
        }
        if (!empty($date)) {
            $query->where('date', $date);
        }
        $query->orderBy('score', 'desc')
            //   ->orderBy('streak_length', 'desc')
              ->limit($limit);
        return $query->paginate($limit);
    }
    /**
     * Lấy danh sách các loại insight
     */
    public function getTypes(): array
    {
        return [
            FormulaHeatmapInsight::TYPE_LONG_RUN => 'Long Run',
            FormulaHeatmapInsight::TYPE_LONG_RUN_STOP => 'Long Run Stop', 
            FormulaHeatmapInsight::TYPE_REBOUND => 'Rebound'
        ];
    }

    /**
     * Lấy danh sách các ngày có insight
     */
    public function getAvailableDates()
    {
        return FormulaHeatmapInsight::select('date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->get()
            ->pluck('date');
    }

    
} 