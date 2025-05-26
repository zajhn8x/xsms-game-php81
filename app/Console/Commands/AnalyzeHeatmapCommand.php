<?php

namespace App\Console\Commands;

use App\Models\FormulaHeatmapInsight;
use App\Services\FormulaHitService;
use App\Services\Commands\HeatmapInsightCommandService;
use App\Services\LotteryIndexResultsService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnalyzeHeatmapCommand extends Command
{
    protected $signature = 'heatmap:analyze {--date= : Ngày cần phân tích (YYYY-MM-DD)} {--days=20 : Số ngày cần phân tích}';
    protected $description = 'Phân tích heatmap và lưu insights vào database';

    private FormulaHitService $formulaHitService;
    private LotteryIndexResultsService $lotteryIndexResultsService;

    public function __construct(
        FormulaHitService $formulaHitService,
        LotteryIndexResultsService $lotteryIndexResultsService
    ) {
        parent::__construct();
        $this->formulaHitService = $formulaHitService;
        $this->lotteryIndexResultsService = $lotteryIndexResultsService;
    }

    public function handle()
    {
        try {
            $this->info('Bắt đầu phân tích heatmap...');

            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
            $days = 30; // Luôn lấy 30 ngày trước đó

            // Lấy dữ liệu heatmap từ DB
            $startDate = $date->copy()->subDays($days - 1);
            $records = \App\Models\HeatmapDailyRecord::whereBetween('date', [$startDate, $date])
                ->orderBy('date', 'desc')
                ->get();
            if ($records->isEmpty()) {
                $this->error('Không tìm thấy dữ liệu heatmap trong khoảng ngày!');
                return 1;
            }
            $heatmap = [];
            foreach ($records as $record) {
                $heatmap[$record->date->format('Y-m-d')] = [
                    'data' => $record->data
                ];
            }
            $this->processAllDates($heatmap);

            $this->info('Phân tích hoàn tất!');
            return 0;
        } catch (\Exception $e) {
            Log::error('Lỗi khi phân tích heatmap: ' . $e->getMessage());
            $this->error('Có lỗi xảy ra: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Xử lý một ngày cụ thể
     */
    private function processSingleDate(string $date, array $heatmap): void
    {
        if (!isset($heatmap[$date])) {
            $this->error("Không tìm thấy dữ liệu cho ngày {$date}");
            return;
        }

        $this->info("Đang phân tích ngày {$date}...");
        FormulaHeatmapInsight::where('date', $date)->delete();
        $service = new HeatmapInsightCommandService($this->lotteryIndexResultsService);
        $service->process([$date => $heatmap[$date]], $date);
        $this->info("Đã hoàn thành phân tích ngày {$date}");
    }

    /**
     * Xử lý toàn bộ dữ liệu
     */
    private function processAllDates(array $heatmap): void
    {
        $this->info('Đang phân tích toàn bộ dữ liệu...');
        $dates = array_keys($heatmap);
        FormulaHeatmapInsight::whereIn('date', $dates)->delete();
        $service = new HeatmapInsightCommandService($this->lotteryIndexResultsService);
        $service->process($heatmap, $dates[0]);
        $this->info('Đã hoàn thành phân tích toàn bộ dữ liệu');
    }

    /**
     * Phân tích long run
     */
    protected function analyzeLongRun(array $node, string $date): ?array
    {
        if ($node['streak'] < 6) {
            return null;
        }

        return [
            'type' => FormulaHeatmapInsight::TYPE_LONG_RUN,
            'extra' => [
                'streak_length' => $node['streak'],
                'value' => $node['value'],
                'hit' => $node['hit']
            ]
        ];
    }

    /**
     * Phân tích long run stop
     */
    protected function analyzeLongRunStop(array $heatmap, string $date, array $node): ?array
    {
        // Kiểm tra ngày trước đó
        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        if (!isset($heatmap[$prevDate])) {
            return null;
        }

        // Tìm node trong ngày trước
        $prevNode = collect($heatmap[$prevDate]['data'])
            ->firstWhere('id', $node['id']);

        if (!$prevNode || $prevNode['streak'] < 6 || $node['hit'] !== false) {
            return null;
        }

        return [
            'type' => FormulaHeatmapInsight::TYPE_LONG_RUN_STOP,
            'extra' => [
                'streak_length' => $prevNode['streak'],
                'day_stop' => 1,
                'value' => $node['value'],
                'hit' => $node['hit']
            ]
        ];
    }

    /**
     * Phân tích rebound
     */
    protected function analyzeRebound(array $heatmap, string $date, array $node): ?array
    {
        // Kiểm tra 2 ngày trước đó
        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $prevPrevDate = date('Y-m-d', strtotime($date . ' -2 days'));

        if (!isset($heatmap[$prevDate]) || !isset($heatmap[$prevPrevDate])) {
            return null;
        }

        // Tìm node trong các ngày trước
        $prevNode = collect($heatmap[$prevDate]['data'])
            ->firstWhere('id', $node['id']);
        $prevPrevNode = collect($heatmap[$prevPrevDate]['data'])
            ->firstWhere('id', $node['id']);

        if (!$prevNode || !$prevPrevNode ||
            $prevPrevNode['streak'] < 6 ||
            $prevNode['hit'] !== false ||
            $node['hit'] !== true) {
            return null;
        }

        return [
            'type' => FormulaHeatmapInsight::TYPE_REBOUND,
            'extra' => [
                'streak_length' => $prevPrevNode['streak'],
                'day_stop' => 1,
                'step_1' => true,
                'step_2' => false,
                'value' => $node['value'],
                'hit' => $node['hit'],
                'rebound_success' => true
            ]
        ];
    }
}