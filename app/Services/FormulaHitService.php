<?php

namespace App\Services;

use App\Models\LotteryFormula;
use App\Models\FormulaHit;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use function PHPUnit\Framework\isEmpty;

class FormulaHitService
{
    protected $resultIndexService;

    public function __construct(LotteryResultService $resultService, LotteryIndexResultsService $resultIndexService)
    {
        $this->resultService = $resultService;
        $this->resultIndexService = $resultIndexService;
    }




    /**
     * Lấy danh sách công thức có streak liên tiếp
     */
    public function getStreakFormulas($fromDate, $streak = 2, $limit = 3)
    {
        return FormulaHit::withStreak($fromDate, $streak)
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy dữ liệu timeline cho một công thức
     */
    /**
     * Lấy dữ liệu streak cho biểu đồ
     */
    public function getStreakData(array $hits, array $dateRange): array
    {
        $streakData = [];
        $prevStreak = 0;

        foreach ($dateRange as $date) {
            $hit = $hits[$date] ?? null;
            $streak = 0;

            if ($hit) {
                $streak = isset($streakData[array_key_last($streakData)])
                    ? $streakData[array_key_last($streakData)] + 1
                    : 1;
                if ($streak > 5) $streak = 5;
            } else if ($prevStreak > 0) {
                $streak = -1; // Đánh dấu gián đoạn
            }

            if ($streak != 0) {
                $streakData[$date] = $streak;
            }
            $prevStreak = $streak > 0 ? $streak : 0;
        }

        return $streakData;
    }

    public function getTimelineData(LotteryFormula $cauLo, Carbon $startDate, int $daysBack = 30): array
    {
        // Get date range
        $endDate = $startDate->copy()->subDays($daysBack);
        $dateRange = [];
        $currentDate = $startDate->copy();

        while ($currentDate >= $endDate) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->subDay();
        }

        // Get hits for this cau lo in date range
        $hits = FormulaHit::where('cau_lo_id', $cauLo->id)
            ->whereBetween('ngay', [$endDate, $startDate])
            ->get()
            ->keyBy('ngay');

        // Get lottery results for the date range
        $results = LotteryResult::whereBetween('draw_date', [$endDate, $startDate])
            ->get()
            ->keyBy('draw_date');

        // Get lottery results index for data range
        $resultIndexs = $this->resultIndexService->getDrawDates(
            $cauLo->formula->positions,
            $endDate->format('Y-m-d'),
            $startDate->format('Y-m-d')
        );

        // Xử lý Pair $resultIndexs tạo các cặp số ghép
        foreach ($resultIndexs as $date => &$values) {
            $pairs = [];
            if (count($values) > 1) {
                for ($i = 0; $i < count($values); $i++) {
                    for ($j = $i + 1; $j < count($values); $j++) {
                        $pairs[] = "{$values[$i]}{$values[$j]}";
                        $pairs[] = "{$values[$j]}{$values[$i]}";
                    }
                }
            }
            $resultIndexs[$date] = [
                'values' => $values,
                'pairs' => array_values(array_unique($pairs))
            ];
        }

        // Get meta information
        $meta = [
            'formula_name' => $cauLo->formula->name ?? '',
            'formula_structure' => $cauLo->formula->structure ?? '',
            'total_hits' => $hits->count(),
            'hit_rate' => count($dateRange) > 0
                ? ($hits->count() / count($dateRange)) * 100
                : 0
        ];

        return [
            'meta' => $meta,
            'dateRange' => $dateRange,
            'hits' => $hits,
            'results' => $results,
            'resultIndexs' => $resultIndexs
        ];
    }

    /**
     * Tìm các công thức trúng liên tiếp
     */
    public function findConsecutiveHits($date, $days = 3)
    {
        return FormulaHit::withConsecutiveDays($days, $date)->get();
    }

    /**
     * Lấy thông tin hit và streak theo danh sách cầu lô và ngày tương ứng
     *
     * @param array $cauLoIds
     * @param array $dates
     * @return array
     */
    public function getHitDataWithStreak(array $cauLoIds, array $dates): array
    {
        $results = [];

        foreach ($cauLoIds as $index => $cauLoId) {
            $date = $dates[$index] ?? null;

            if (!$date) continue;

            $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                ->where('ngay', $date)
                ->first();

            $streak = $this->calculateStreak($cauLoId, $date);

            $results[$date][$cauLoId] = [
                'cau_lo_id' => $cauLoId,
                'ngay' => $date,
                'so_trung' => $hit->so_trung ?? null,
                'status' => $hit->status ?? 0,
                'streak' => $streak
            ];
        }

        return $results;
    }
    /**
     * ==================================Heatmap==============
     */

    /**
     * Lấy dữ liệu heatmap cho 100 ID trong 20 ngày
     * @param string $endDate Ngày kết thúc format Y-m-d
     * @return array Mảng dữ liệu heatmap theo ngày
     */
    public function getHeatMap($endDate = null)
    {
        $lotteryFormulaService = new LotteryFormulaService();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::today();
        $startDate = $endDate->copy()->subDays(7); // 20 ngày: từ start đến end
        $currentDate = $startDate->copy();
        $trackedIds = [];
        $heatmapData = [];
        $allDates = [];

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = [];
            $seenIds = [];
            $idsNextDay = [];
            for ($streak = 7; $streak >= 2; $streak--) {
                $hits = $this->getStreakFormulas($dateStr, $streak, 20); // bỏ limit nếu không cần

                foreach ($hits as $hit) {
                    $id = $hit->cau_lo_id;
                    if (isset($seenIds[$id])) continue;

                    $dayData[$id] = [
                        'id' => $id,
                        'streak' => $streak,
                        'value' => $id,
                        // 'hit' => null,
                        // 'status' => 0
                    ];

                    $seenIds[$id] = true;
                    if($streak >= 4){
                        $trackedIds[$id] = true; // ➕ luôn cả ID mới
                    }
                }
            }
            $dateValues =  $lotteryFormulaService->getValuePositionFormulasByDate(array_keys($trackedIds), $dateStr);
            $headsTails = $this->getLotteryListByFormulas($dateValues);
//dump($headsTails);

            //ID tracking là ID chưa lấy streak ở trên nhưng cần track
            $idsNextDay = array_diff_key($trackedIds,  $seenIds);
            if(!empty($idsNextDay)){
                $dayData2 =  $this->getHitData(array_keys($idsNextDay), [$dateStr]);
                $dayData += $dayData2[$dateStr];
            }


            $dayData = array_values($dayData);
            usort($dayData, fn($a, $b) => $b['streak'] <=> $a['streak']);
            // $heatmapData[$dateStr] = ["data" =>$dayData, "heads-tails" =>  $headsTails] ;
            $heatmapData[$dateStr] = ["data" =>$dayData] ;
            $allDates[] = $dateStr;
            $currentDate->addDay();
        }
//        dump($heatmapData);
//        dump($allDates);
        // ✅ Lấy toàn bộ thông tin hit/status
//        $hitData = $this->getHitData(array_keys($trackedIds), $allDates);



        // ✅ Gộp lại
        $finalHeatmap = [];

//        foreach ($hitData as $date => $row){
//            $finalHeatmap = collect($row)->merge($heatmapData[$date])->toArray();
//        }
//        dump($finalHeatmap);

//        $aaa = collect($hitData)->merge($heatmapData)->toArray();
//        dump($aaa);
        return $heatmapData;
    }

    /**
     * Tính streak của 1 cầu lô tới ngày $date
     *
     * @param string|int $cauLoId
     * @param string $date (Y-m-d)
     * @return int
     */
    private function calculateStreak($cauLoId, $date): int
    {
        $streak = 0;
        $currentDate = Carbon::parse($date);

        while (true) {
            $hit = FormulaHit::where('cau_lo_id', $cauLoId)
                ->where('ngay', $currentDate->toDateString())
                ->exists();

            if ($hit) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    public function getHitData(array $cauLoIds, array $dates): array
    {
        $hits = FormulaHit::whereIn('cau_lo_id', $cauLoIds)
            ->whereIn('ngay', $dates)
            ->get();

        $result = [];

        foreach ($hits as $hit) {
            $date = $hit->ngay;
            $id = $hit->cau_lo_id;

            $result[$date][$id] = [
                'id' => $id,
                'value' => $id,
                'hit' => $hit->so_trung,
                'status' => $hit->status,
                'streak' => 1,
            ];
        }

        // ✅ Đảm bảo mọi cặp ID – ngày đều có
        foreach ($dates as $date) {
            foreach ($cauLoIds as $id) {
                if (!isset($result[$date][$id])) {
                    $result[$date][$id] = [
                        'id' => $id,
                        'hit' => null,
                        'status' => 0,
                        'streak' => 0,
                        'value' =>$id

                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Generate lottery number lists (normal & forward) from formula results
     *
     * @param array $formulasData  // Output from getValuePositionFormulasByDate
     * @return array [
     *   'normal' => ['05' => [2], '50' => [2]],
     *   'forward_only' => ['05' => [2]]
     * ]
     */
    public function getLotteryListByFormulas(array $formulasData): array
    {
        $normal = [];
        $forwardOnly = [];

        foreach ($formulasData as $item) {
            $id = $item['id'];
            $valueStrings = $item['value_string'] ?? [];

            if (count($valueStrings) !== 2) {
                continue;
            }

            [$xy, $yx] = $valueStrings;

            // Add both directions to "normal"
            $normal[$xy][] = $id;
            $normal[$yx][] = $id;

            // Only forward order to "forward_only"
            $forwardOnly[$xy][] = $id;
        }

        return [
            'normal' => $normal,
            'forward_only' => $forwardOnly,
        ];
    }

    /**
     * ==================================END Heatmap==============
     */
}
