
<?php

namespace App\Services;

use App\Models\LotteryResult;
use App\Contracts\LotteryResultServiceInterface;
use Carbon\Carbon;

class LotteryResultService implements LotteryResultServiceInterface
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
}
