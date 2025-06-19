<?php

namespace App\Services\Commands;

class HeatmapInsightCommandService
{
    /**
     * Tạo insight TYPE_LONG_RUN
     */
    public function createLongRunInsight($id, $date, $streak, $value, $suggests)
    {
        return self::createInsight(
            $id,
            $date,
            \App\Models\FormulaHeatmapInsight::TYPE_LONG_RUN,
            [
                'streak_length' => $streak,
                'value' => $value,
                'suggests' => $suggests
            ],
            $streak
        );
    }

    /**
     * Tạo insight TYPE_LONG_RUN_STOP
     */
    public function createLongRunStopInsight($id, $date, $prevStreak, $stopDays, $value, $suggests)
    {
        return self::createInsight(
            $id,
            $date,
            \App\Models\FormulaHeatmapInsight::TYPE_LONG_RUN_STOP,
            [
                'streak_length' => $prevStreak,
                'stop_days' => $stopDays,
                'value' => $value,
                'suggests' => $suggests
            ],
            $prevStreak - $stopDays
        );
    }

    /**
     * Tạo insight TYPE_REBOUND
     */
    public function createReboundInsight($id, $date, $prevStreak, $stopDays, $running, $step_1, $step_2, $step_3, $value, $suggests)
    {
        return self::createInsight(
            $id,
            $date,
            \App\Models\FormulaHeatmapInsight::TYPE_REBOUND,
            [
                'streak_length' => $prevStreak,
                'stop_days' => $stopDays,
                'running' => $running,
                'step_1' => $step_1,
                'step_2' => $step_2,
                'step_3' => $step_3,
                'value' => $value,
                'suggests' => $suggests
            ],
            $prevStreak
        );
    }

    /**
     * Xử lý toàn bộ heatmap và tạo insights
     */
    public function process(array $heatmap, $anchor_date, $streak_min = 4, $day_stops_max = 6, $running_max = 3): void
    {
        $dates = array_keys($heatmap);
        rsort($dates); // Ngày mới nhất trước

        // Chỉ lấy các ngày <= anchor_date
        $dates = array_filter($dates, fn($d) => $d <= $anchor_date);
        $dates = array_values($dates); // reset key

        // Bước 1: Lấy id theo ngày, không trùng lặp, lưu theo mảng ngày
        $ids_by_date = [];
        $ids_seen = [];
        foreach ($dates as $date) {
            foreach ($heatmap[$date]['data'] as $item) {
                if ($item['streak'] >= $streak_min && !in_array($item['id'], $ids_seen)) {
                    $ids_by_date[$date][] = $item['id'];
                    $ids_seen[] = $item['id'];
                }
            }
        }

        // Bước 2: Tạo timeline cho từng id
        foreach ($ids_by_date as $date => $ids) {
            foreach ($ids as $id) {
                $timeline = [];
                $start_idx = array_search($date, $dates);
                for ($i = 0; $i <= $start_idx; $i++) {
                    foreach ($heatmap[$dates[$i]]['data'] as $item) {
                        if ($item['id'] == $id) {
                            $timeline[] = [
                                'date' => $dates[$i],
                                'streak' => $item['streak'],
                                'value' => $item['value'] ?? null,
                                'suggests' => is_array($item['suggest'] ?? null) ? $item['suggest'] : explode(',', $item['suggest'] ?? ''),
                            ];
                        }
                    }
                }

                // Xử lý TYPE_LONG_RUN
                if ($timeline[0]['streak'] >= 6) {
                    $this->createLongRunInsight(
                        $id,
                        $timeline[0]['date'],
                        $timeline[0]['streak'],
                        $timeline[0]['value'],
                        $timeline[0]['suggests']
                    );
                }

                // Xử lý TYPE_LONG_RUN_STOP
                if (count($timeline) > 1 && $timeline[1]['streak'] >= 6 && $timeline[0]['streak'] == 0) {
                    $this->createLongRunStopInsight(
                        $id,
                        $timeline[0]['date'],
                        $timeline[1]['streak'],
                        1,
                        $timeline[0]['value'],
                        $timeline[0]['suggests']
                    );
                }

                // Bước 3: Duyệt timeline để phát hiện TYPE_REBOUND
                $streaks = array_map(fn($item) => $item['streak'], $timeline);

                $streak_length = null;
                $stop_days = 0;
                $running = null;
                $step_1 = null;
                $step_2 = false;
                $step_3 = false;
                $flag_stop_days = false;
                $running_max_value = 0;

                for ($j = count($streaks) - 1; $j >= 0; $j--) {
                    $streak = $streaks[$j];

                    if ($streak >= $streak_min && $streak_length === null) {
                        $streak_length = $streak;
                        continue;
                    }

                    if ($streak_length !== null) {
                        if ($streak == 0 && !$flag_stop_days) {
                            if ($stop_days < $day_stops_max) {
                                $stop_days++;
                            } else {
                                break;
                            }
                        }
                        else if ($streak == 1 && $j == 0 && $flag_stop_days == 0) {
                            $running = "step_1";
                            $step_1 = null;
                            $step_2 = null;
                            $step_3 = null;
                        }
                        else if ($streak == 1 && $j != 0 && $j < ($running_max)) {
                            $flag_stop_days = true;
                            $running_max_value++;
                            $running = "step_1";
                            $step_1 = null;
                            $step_2 = null;
                            $step_3 = null;
                        } else if($running_max_value > 0 && $flag_stop_days) {
                            $running_max_value++;
                            switch($running_max_value) {
                                case 2:
                                    $running = "step_2";
                                    $step_1 = $streak > 0 ? "hit" : "miss";
                                    break;
                                case 3:
                                    $running = "step_3";
                                    $step_2 = $streak > 0 ? "hit" : "miss";
                                    $step_3 = null;
                                    break;
                            }
                        } else {
                            break;
                        }

                        if ($timeline[$j]['date'] == $anchor_date && $stop_days > 0 && $running !== null && $j == 0) {
                            $this->createReboundInsight(
                                $id,
                                $timeline[$j]['date'],
                                $streak_length,
                                $stop_days,
                                $running,
                                $step_1,
                                $step_2,
                                $step_3,
                                $timeline[$j]['value'],
                                $timeline[$j]['suggests']
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Tạo insight
     */
    public static function createInsight(
        int $formulaId,
        string $date,
        string $type,
        array $extra,
        float $score = 0
    ): \App\Models\FormulaHeatmapInsight {
        return \App\Models\FormulaHeatmapInsight::updateOrCreate(
            ['formula_id' => $formulaId, 'date' => $date],
            [
                'type' => $type,
                'extra' => $extra,
                'score' => $score
            ]
        );
    }
}
