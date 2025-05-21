<?php

namespace App\Console\Commands;

use App\Models\FormulaHeatmapInsight;
use App\Services\FormulaHitService;
use App\Services\HeatmapInsightService;
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
            
            // Lấy dữ liệu heatmap
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
            $days = (int)$this->option('days');
            
            $heatmap = $this->formulaHitService->getHeatMap($date);
            
            if (empty($heatmap)) {
                $this->error('Không tìm thấy dữ liệu heatmap!');
                return 1;
            }

            // Xử lý theo ngày cụ thể hoặc toàn bộ
            if ($this->option('date')) {
                $this->processSingleDate($date->format('Y-m-d'), $heatmap);
            } else {
                $this->processAllDates($heatmap);
            }

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
        
        // Xóa dữ liệu cũ của ngày này
        FormulaHeatmapInsight::where('date', $date)->delete();
        
        // Phân tích và lưu dữ liệu mới
        $service = new HeatmapInsightService([$date => $heatmap[$date]], $this->lotteryIndexResultsService);
        $service->process();

        $this->info("Đã hoàn thành phân tích ngày {$date}");
    }

    /**
     * Xử lý toàn bộ dữ liệu
     */
    private function processAllDates(array $heatmap): void
    {
        $this->info('Đang phân tích toàn bộ dữ liệu...');
        
        // Xóa dữ liệu cũ
        $dates = array_keys($heatmap);
        FormulaHeatmapInsight::whereIn('date', $dates)->delete();
        
        // Phân tích và lưu dữ liệu mới
        $service = new HeatmapInsightService($heatmap, $this->lotteryIndexResultsService);
        $service->process();

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