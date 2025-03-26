<?php

namespace App\Services;

use App\Models\FormulaStatistic;
use App\Models\LotteryFormula;
use Carbon\Carbon;

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
}
