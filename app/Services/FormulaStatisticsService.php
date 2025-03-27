<?php

namespace App\Services;

use App\Models\FormulaHit;
use App\Models\FormulaStatistic;
use App\Models\LotteryFormula;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_encode;

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
        // Lấy danh sách các lần trúng thưởng theo công thức và khoảng thời gian nhất định
        $hits = FormulaHit::where('cau_lo_id', $formulaId)
            ->whereBetween('ngay', [$startDate, $endDate])
            ->orderBy('ngay', 'asc')
            ->get();

        if ($hits->isEmpty()) {
            return; // Không có dữ liệu thì dừng
        }

        // Nhóm dữ liệu theo quý và năm
        $groupedHits = $hits->groupBy(function ($hit) {
            $year = Carbon::parse($hit->ngay)->year;
            $quarter = ceil(Carbon::parse($hit->ngay)->month / 3);
            return "$year-Q$quarter";
        });


        foreach ($groupedHits as $quarterYear => $quarterHits) {
            // Chuyển danh sách ngày trúng thành danh sách Carbon object và sắp xếp tăng dần
            $dates = $quarterHits->pluck('ngay')->map(fn($d) => Carbon::parse($d))->sort();
            if ($dates->isEmpty()) {
                continue;
            }

            [$year, $quarter] = explode('-Q', $quarterYear);
            $year = (int) $year;
            $quarter = (int) $quarter;

            // Tổng số lần trúng trong quý
            $frequency = $dates->count();

            // Tính chu kỳ trúng trung bình (khoảng thời gian trung bình giữa các lần trúng)
            $winCycle = 0;
            if ($frequency > 1) {
                $winCycle = $dates->skip(1)->zip($dates)
                    ->map(fn($pair) => $pair[0] instanceof Carbon && $pair[1] instanceof Carbon ? $pair[0]->diffInDays($pair[1]) : 0)
                    ->average();
            }

            // Xác suất trúng trong quý (tính theo số ngày có trúng trên tổng số ngày trong quý)
            $totalDays = $dates->last()->diffInDays($dates->first()) + 1;
            $probability = $totalDays > 0 ? round(($frequency / $totalDays), 2) : 0;

            // Tính các streak (chuỗi ngày trúng liên tiếp)
            $streaks = [2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 'more_6' => 0];
            $currentStreak = 1;
            $maxStreak = 1;

            foreach ($dates->skip(1)->zip($dates) as $pair) {
                [$current, $previous] = $pair;
                if ($current instanceof Carbon && $previous instanceof Carbon) {
                    if ($current->diffInDays($previous) == 1) { // Ngày kế tiếp
                        $currentStreak++;
                    } else {
                        // Nếu chuỗi liên tiếp đạt tối thiểu 2 ngày thì lưu lại
                        if ($currentStreak >= 2) {
                            $key = $currentStreak > 6 ? 'more_6' : $currentStreak;
                            $streaks[$key]++;
                        }
                        $maxStreak = max($maxStreak, $currentStreak);
                        $currentStreak = 1; // Reset chuỗi streak
                    }
                }
            }
            // Kiểm tra lần cuối nếu chuỗi streak vẫn đang chạy
            if ($currentStreak >= 2) {
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

            Log::info(json_encode([
                ['formula_id' => $formulaId, 'year' => $year, 'quarter' => $quarter],
                [
                    'frequency' => $frequency,
                    'win_cycle' => round($winCycle),
                    'probability' => $probability,
                    'streak_2' => $streaks[2],
                    'streak_3' => $streaks[3],
                    'streak_4' => $streaks[4],
                    'streak_5' => $streaks[5],
                    'streak_6' => $streaks[6],
                    'streak_more_6' => $streaks['more_6'],
                    'prev_streak' => $prevStreak,
                    'last_streak' => $maxStreak, // Lưu chuỗi streak cuối cùng của quý này
                    'days_max_streak' => $maxStreak, // Số ngày liên tiếp trúng lớn nhất trong quý
                ]]));

            // Lưu thống kê vào database
            FormulaStatistic::updateOrCreate(
                ['formula_id' => $formulaId, 'year' => $year, 'quarter' => $quarter],
                [
                    'frequency' => $frequency,
                    'win_cycle' => round($winCycle),
                    'probability' => $probability,
                    'streak_2' => $streaks[2],
                    'streak_3' => $streaks[3],
                    'streak_4' => $streaks[4],
                    'streak_5' => $streaks[5],
                    'streak_6' => $streaks[6],
                    'streak_more_6' => $streaks['more_6'],
                    'prev_streak' => $prevStreak,
                    'last_streak' => $maxStreak, // Lưu chuỗi streak cuối cùng của quý này
                    'days_max_streak' => $maxStreak, // Số ngày liên tiếp trúng lớn nhất trong quý
                ]
            );
        }
    }
}
