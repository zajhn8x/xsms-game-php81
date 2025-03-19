
<?php

namespace App\Services;

use App\Models\LotteryResult;
use Carbon\Carbon;

class LotteryResultService
{
    public function getLatestResults($limit = 10)
    {
        return LotteryResult::orderBy('draw_date', 'desc')
            ->take($limit)
            ->get();
    }

    public function getResultsByDateRange($startDate, $endDate)
    {
        return LotteryResult::whereBetween('draw_date', [
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        ])->get();
    }

    public function createResult(array $data)
    {
        return LotteryResult::create([
            'draw_date' => Carbon::parse($data['draw_date']),
            'prizes' => $data['prizes'],
            'lo_array' => $data['lo_array']
        ]);
    }

    public function analyzeFrequency($days = 30)
    {
        $results = LotteryResult::where('draw_date', '>=', Carbon::now()->subDays($days))
            ->get();
        
        $frequency = [];
        foreach ($results as $result) {
            foreach ($result->lo_array as $number) {
                $frequency[$number] = ($frequency[$number] ?? 0) + 1;
            }
        }
        
        arsort($frequency);
        return $frequency;
    }
}
