<?php

namespace App\Services;

use App\Models\FormulaHeatmapInsight;
use App\Models\LotteryFormula;
use App\Services\LotteryIndexResultsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HeatmapInsightService
{
    private array $heatmap;
    private $lotteryIndexResultsService;

    public function __construct(array $heatmap, LotteryIndexResultsService $lotteryIndexResultsService)
    {
        $this->heatmap = $heatmap;
        $this->lotteryIndexResultsService = $lotteryIndexResultsService;
    }

    /**
     * Xử lý toàn bộ heatmap và tạo insights
     */
    public function process(): void
    {
        $history = [];
        $dates = array_keys($this->heatmap);
        sort($dates); // Sắp xếp ngày tăng dần
        $dateCount = count($dates);

        foreach ($dates as $i => $date) {
            $day = $this->heatmap[$date];
            if (empty($day['data'])) continue;

            // Rollback: xử lý cho tất cả các ngày, không chỉ ngày cuối cùng
            // Lấy tất cả id cầu lô trong ngày
            $ids = array_column($day['data'], 'id');
            // Lấy predicted values cho tất cả cầu trong ngày
            $predictedMap = [];
            $predictedArr = $this->lotteryIndexResultsService->getPositionsByFormulaIds($ids, $date);
            foreach ($predictedArr as $itemPred) {
                $predictedMap[$itemPred['id']] = $itemPred['position'];
            }

            foreach ($day['data'] as $item) {
                $id = $item['id'];
                $streak = $item['streak'];
                $hit = $item['hit'] ?? null;
                $value = $item['value'];
                $predicted = $predictedMap[$id] ?? [];
                $formula = \App\Models\LotteryFormula::find($id);
                $positions = [];
                if ($formula && isset($formula->positions)) {
                    $positions = $formula->positions;
                }

                // Nếu streak >= 6: long_run
                if ($streak >= 6) {
                    $history[$id]['last_streak'] = $streak;
                    $history[$id]['is_long_run'] = true;
                    $history[$id]['day_stop'] = 0;

                    FormulaHeatmapInsight::createInsight(
                        $id,
                        $date,
                        FormulaHeatmapInsight::TYPE_LONG_RUN,
                        [
                            'streak_length' => $streak,
                            'value' => $value,
                            'hit' => $hit,
                            'predicted_values_by_position' => $predicted,
                            'positions' => $positions,
                        ],
                        $streak
                    );
                    continue;
                }

                // Nếu vừa kết thúc long_run và hôm nay không hit: bắt đầu hoặc tiếp tục long_run_stop
                if (!empty($history[$id]['is_long_run']) && ($hit === false || $hit === null)) {
                    $history[$id]['day_stop'] = ($history[$id]['day_stop'] ?? 0) + 1;
                    $dayStop = $history[$id]['day_stop'];

                    if ($dayStop <= 3) {
                        FormulaHeatmapInsight::createInsight(
                            $id,
                            $date,
                            FormulaHeatmapInsight::TYPE_LONG_RUN_STOP,
                            [
                                'streak_length' => $history[$id]['last_streak'],
                                'day_stop' => $dayStop,
                                'value' => $value,
                                'hit' => $hit,
                                'predicted_values_by_position' => $predicted,
                                'positions' => $positions,
                            ],
                            $history[$id]['last_streak'] - $dayStop
                        );
                    }

                    // Nếu dừng quá 3 ngày thì không còn là long_run_stop nữa
                    if ($dayStop >= 3) {
                        unset($history[$id]['is_long_run']);
                    }
                    continue;
                }

                // rebound_after_long_run: nếu streak=1, hit!=null, và trong 3 ngày trước đó từng có streak>=4
                if ($streak == 1 && !empty($hit)) {
                    $isRebound = false;
                    for ($j = $i - 1; $j >= max(0, $i - 3); $j--) {
                        $prevDate = $dates[$j];
                        $prevData = $this->heatmap[$prevDate]['data'] ?? [];
                        foreach ($prevData as $prevItem) {
                            if ($prevItem['id'] == $id && $prevItem['streak'] >= 4) {
                                $isRebound = true;
                                $prevStreak = $prevItem['streak'];
                                break 2;
                            }
                        }
                    }
                    if ($isRebound) {
                        FormulaHeatmapInsight::createInsight(
                            $id,
                            $date,
                            FormulaHeatmapInsight::TYPE_REBOUND,
                            [
                                'streak_length' => $prevStreak ?? 0,
                                'day_stop' => null,
                                'step_1' => true,
                                'step_2' => false,
                                'value' => $value,
                                'hit' => $hit,
                                'rebound_success' => true,
                                'predicted_values_by_position' => $predicted,
                                'positions' => $positions,
                            ],
                            ($prevStreak ?? 0) + 2
                        );
                        // Reset trạng thái dừng
                        $history[$id]['day_stop'] = 0;
                        unset($history[$id]['is_long_run']);
                        continue;
                    }
                }

                // Nếu hit lại mà không phải rebound thì reset day_stop
                if ($hit === true) {
                    $history[$id]['day_stop'] = 0;
                    unset($history[$id]['is_long_run']);
                }
            }
        }
    }

    /**
     * Create a new insight
     */
    public static function createInsight(
        int $formulaId,
        string $date,
        string $type,
        array $extra,
        float $score = 0
    ): self {
        return FormulaHeatmapInsight::updateOrCreate(
            ['formula_id' => $formulaId, 'date' => $date],
            [
                'type' => $type,
                'extra' => $extra,
                'score' => $score
            ]
        );
    }

    /**
     * Lấy danh sách insight tốt nhất theo điều kiện
     */
    public function getTopInsights($limit = 3, $filters = [])
    {
        $query = FormulaHeatmapInsight::query();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['day_stop'])) {
            $query->where('day_stop', $filters['day_stop']);
        }

        if (!empty($filters['streak_length'])) {
            $query->where('streak_length', '>=', $filters['streak_length']);
        }

        // Sắp xếp theo điểm số và streak
        $query->orderBy('score', 'desc')
              ->orderBy('streak_length', 'desc')
              ->limit($limit);

        return $query->get();
    }
} 