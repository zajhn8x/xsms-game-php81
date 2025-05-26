<?php

namespace App\Jobs;

use App\Models\HeatmapDailyRecord;
use App\Services\FormulaHitService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateHeatmapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $from;
    private $to;
    private $retry;
    private $formulaHitService;

    public function __construct($from, $to, $retry = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->retry = $retry;
        $this->formulaHitService = app(FormulaHitService::class);
    }

    public function handle()
    {
        Log::info('Bắt đầu xử lý GenerateHeatmapJob', [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'retry' => $this->retry
        ]);

        $currentDate = Carbon::parse($this->from);
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        while ($currentDate <= $this->to) {
            try {
            Log::info('Đang xử lý ngày: ' . $currentDate->format('Y-m-d'));

                // Lấy dữ liệu heatmap từ service cho ngày hiện tại
                $heatmapData = $this->formulaHitService->getHeatMap($currentDate);
                $date = $currentDate->format('Y-m-d');
                // Log::info('Dữ liệu heatmap: ' . json_encode($heatmapData));
                // die();
                    // Lấy data của ngày hiện tại
                if ($heatmapData && isset($heatmapData[$date])) {

                    if (isset($heatmapData[$date])) {
                        if (!$this->retry && HeatmapDailyRecord::where('date', $date)->exists()) {
                            Log::info('Bỏ qua ngày đã tồn tại: ' . $date);
                            $skippedCount++;
                            $currentDate = $currentDate->addDays(1);
                            continue;
                        }

                        HeatmapDailyRecord::updateOrCreate(
                            [
                                'date' => $date
                            ],
                            [
                                'data' => $heatmapData[$date]['data']
                            ]
                        );

                        Log::info('Đã lưu thành công data cho ngày: ' . $date);
                        $processedCount++;
                    } else {
                        Log::warning('Không tìm thấy data cho ngày: ' . $date);
                    }
                } else {
                    Log::warning('Không có dữ liệu heatmap cho ngày: ' . $currentDate->format('Y-m-d'));
                }
            } catch (\Exception $e) {
                Log::error('Lỗi khi xử lý ngày ' . $currentDate->format('Y-m-d'), [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }

            // Tăng 5 ngày
            $currentDate = $currentDate->addDays(1);
        }

        Log::info('Hoàn thành GenerateHeatmapJob', [
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount
        ]);
    }
}
