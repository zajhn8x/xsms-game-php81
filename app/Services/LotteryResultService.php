<?php

namespace App\Services;

use App\Contracts\LotteryResultServiceInterface;
use App\Models\LotteryResult;
use Carbon\Carbon;

class LotteryResultService implements LotteryResultServiceInterface
{
    public function getLatestResults($limit)
    {
        return LotteryResult::orderBy('draw_date', 'desc')
            ->take($limit)
            ->get();
    }

    public function getResultsByDateRange($startDate, $endDate)
    {
        return LotteryResult::whereBetween('draw_date', [$startDate, $endDate])
            ->orderBy('draw_date', 'desc')
            ->get();
    }

    public function createResult(array $data)
    {
        return LotteryResult::create($data);
    }

    public function analyzeFrequency($days)
    {
        $results = LotteryResult::where('draw_date', '>=', Carbon::now()->subDays($days))
            ->get();

        // Implement frequency analysis logic here
        return [];
    }

    public function hasResultForDate($date)
    {
        return LotteryResult::where('draw_date', $date)->exists();
    }
}