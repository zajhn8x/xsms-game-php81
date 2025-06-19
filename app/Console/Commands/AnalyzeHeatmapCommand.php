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
    protected $signature = 'heatmap:analyze {--date= : Ngày cần phân tích (YYYY-MM-DD)} {--days=30 : Số ngày cần phân tích}';
    protected $description = 'Phân tích heatmap và lưu insights vào database';

    private FormulaHitService $formulaHitService;

    public function __construct(FormulaHitService $formulaHitService)
    {
        parent::__construct();
        $this->formulaHitService = $formulaHitService;
    }

    public function handle()
    {
        try {
            $this->info('Bắt đầu phân tích heatmap...');

            // Xử lý ngày
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
            $days = (int)$this->option('days');
            //luôn lấy 20 ngày để quan sát timeline
            $startDate = $date->copy()->subDays(20);

            // Lấy dữ liệu heatmap từ DB
            $records = \App\Models\HeatmapDailyRecord::whereBetween('date', [$startDate, $date])
                ->orderBy('date', 'desc')
                ->get();

            if ($records->isEmpty()) {
                $this->error('Không tìm thấy dữ liệu heatmap trong khoảng ngày!');
                return 1;
            }

            // Chuyển đổi dữ liệu
            $heatmap = [];
            foreach ($records as $record) {
                $heatmap[$record->date->format('Y-m-d')] = [
                    'data' => $record->data
                ];
            }

            // Xóa dữ liệu cũ
            FormulaHeatmapInsight::where('date', $date)->delete();

            // Phân tích dữ liệu
            $service = new HeatmapInsightCommandService();
            $service->process($heatmap, $date->format('Y-m-d'));

            $this->info('Phân tích hoàn tất!');
            return 0;
        } catch (\Exception $e) {
            Log::error('Lỗi khi phân tích heatmap: ' . $e->getMessage());
            $this->error('Có lỗi xảy ra: ' . $e->getMessage());
            return 1;
        }
    }
}