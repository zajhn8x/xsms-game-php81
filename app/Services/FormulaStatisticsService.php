<?php

namespace App\Services;

use App\Models\FormulaHit;
use App\Models\FormulaStatistic;
use App\Models\LotteryFormula;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FormulaStatisticsService
{
    /**
     * Lấy thống kê cầu theo năm và quý
     *
     * @param int $year
     * @param int $quarter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStatisticsByQuarter(int $year, int $quarter)
    {
        return FormulaStatistic::where('year', $year)
            ->where('quarter', $quarter)
            ->orderByDesc('frequency')
            ->get();
    }

    /**
     * Cập nhật last_streak cho quý mới từ prev_streak của quý trước
     */
    public function updateLastStreak()
    {
        FormulaStatistic::query()
            ->join('formula_statistics as prev', function ($join) {
                $join->on('formula_statistics.formula_id', '=', 'prev.formula_id')
                    ->whereRaw('(formula_statistics.year = prev.year + (prev.quarter = 4))')
                    ->whereRaw('(formula_statistics.quarter = IF(prev.quarter = 4, 1, prev.quarter + 1))');
            })
            ->update([
                'formula_statistics.last_streak' => \DB::raw('prev.prev_streak')
            ]);
    }

    /**
     * Cập nhật thống kê cầu theo dữ liệu mới
     *
     * @param int $formulaId
     * @param int $year
     * @param int $quarter
     * @param array $data
     * @return FormulaStatistic
     */
    public function updateStatistics(int $formulaId, int $year, int $quarter, array $data)
    {
        return FormulaStatistic::updateOrCreate(
            [
                'formula_id' => $formulaId,
                'year' => $year,
                'quarter' => $quarter
            ],
            $data
        );
    }

    /**
     * Chọn cầu tối ưu từ danh sách công thức cụ thể
     *
     * @param \Illuminate\Database\Eloquent\Collection $lotteryFormulas
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimalFormulas($lotteryFormulas)
    {
        $topWinningFormulas = $this->filterTopWinningFormulas($lotteryFormulas);
        $topWinningFormulas = $this->filterHighProbabilityFormulas($topWinningFormulas);
        $topWinningFormulas = $this->filterTrendingFormulas($topWinningFormulas);
        $topWinningFormulas = $this->filterLongStreakFormulas($topWinningFormulas);

        return $topWinningFormulas;
    }

    /**
     * Lọc các công thức có tổng số lần trúng cao nhất
     */
    private function filterTopWinningFormulas($lotteryFormulas)
    {
        return $lotteryFormulas->sortByDesc(function ($formula) {
            return $formula->statistics->sum('frequency');
        });
    }

    /**
     * Lọc công thức có xác suất trúng cao nhất trong các quý
     */
    private function filterHighProbabilityFormulas($lotteryFormulas)
    {
        return $lotteryFormulas->filter(function ($formula) {
            return $formula->statistics->max('probability') > 50;
        });
    }

    /**
     * Lọc công thức có xu hướng mạnh lên trong 2-3 quý gần nhất
     */
    private function filterTrendingFormulas($lotteryFormulas)
    {
        return $lotteryFormulas->filter(function ($formula) {
            return $formula->statistics->take(-3)->avg('win_cycle') < 10;
        });
    }

    /**
     * Lọc công thức có chu kỳ trúng dài
     */
    private function filterLongStreakFormulas($lotteryFormulas)
    {
        return $lotteryFormulas->filter(function ($formula) {
            return $formula->statistics->max('streak_more_6') > 0;
        });
    }

    /**
     * Generate statistics from FormulaHit
     */
    public function generateStatisticsFromHits($formulaId, $startDate, $endDate)
    {
        $hits = FormulaHit::where('formula_id', $formulaId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        if ($hits->isEmpty()) {
            return;
        }

        // Nhóm dữ liệu theo quý và năm
        $groupedHits = $hits->groupBy(function ($hit) {
            $year = Carbon::parse($hit->date)->year;
            $quarter = ceil(Carbon::parse($hit->date)->month / 3);
            return "$year-Q$quarter";
        });

        foreach ($groupedHits as $quarterYear => $quarterHits) {
            $dates = $quarterHits->pluck('date')->map(fn($d) => Carbon::parse($d))->sort();
            $year = $dates->first()->year;
            $quarter = ceil($dates->first()->month / 3);

            $frequency = $dates->count();

            // Tính chu kỳ trúng trung bình
            $winCycle = 0;
            if ($frequency > 1) {
                $winCycle = $dates->zip($dates->skip(1))
                    ->map(fn($pair) => $pair[1]->diffInDays($pair[0]))
                    ->average();
            }

            // Xác suất trúng trong quý
            $totalDays = $dates->last()->diffInDays($dates->first()) + 1;
            $probability = $totalDays > 0 ? round(($frequency / $totalDays) * 100, 2) : 0.00;

            // Tính các streak (liên tiếp trúng)
            $streaks = [3 => 0, 4 => 0, 5 => 0, 6 => 0, 'more_6' => 0];
            $currentStreak = 1;
            $maxStreak = 1;

            foreach ($dates->zip($dates->skip(1)) as $pair) {
                if ($pair[1]->diffInDays($pair[0]) == 1) {
                    $currentStreak++;
                } else {
                    if ($currentStreak >= 3) {
                        $key = $currentStreak > 6 ? 'more_6' : $currentStreak;
                        $streaks[$key]++;
                    }
                    $maxStreak = max($maxStreak, $currentStreak);
                    $currentStreak = 1;
                }
            }
            if ($currentStreak >= 3) {
                $key = $currentStreak > 6 ? 'more_6' : $currentStreak;
                $streaks[$key]++;
            }
            $maxStreak = max($maxStreak, $currentStreak);

            // Tìm trạng thái streak của quý trước
            $prevStatistic = FormulaStatistic::where('formula_id', $formulaId)
                ->where('year', $year)
                ->where('quarter', $quarter - 1)
                ->first();
            $prevStreak = $prevStatistic->last_streak ?? 0;

            // Lưu thống kê vào database
            FormulaStatistic::updateOrCreate(
                ['formula_id' => $formulaId, 'year' => $year, 'quarter' => $quarter],
                [
                    'frequency' => $frequency,
                    'win_cycle' => round($winCycle),
                    'probability' => $probability,
                    'streak_3' => $streaks[3],
                    'streak_4' => $streaks[4],
                    'streak_5' => $streaks[5],
                    'streak_6' => $streaks[6],
                    'streak_more_6' => $streaks['more_6'],
                    'prev_streak' => $prevStreak,
                    'last_streak' => $maxStreak,
                ]
            );
        }
    }
}
