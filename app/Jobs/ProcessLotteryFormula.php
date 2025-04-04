<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LotteryFormula;
use App\Services\LotteryFormulaService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessLotteryFormula implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $startDate;
    protected $endDate;
    protected $formulaIds;

    public function __construct($batchId, $startDate = null, $endDate = null, $formulaIds = [])
    {
        $this->batchId = $batchId;
        $this->startDate = $startDate ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $this->endDate = $endDate ?? Carbon::now()->subDays(1)->format('Y-m-d');
        $this->formulaIds = $formulaIds;
    }

    public function handle(LotteryFormulaService $formulaService)
    {
        Log::info("🔹 Bắt đầu xử lý job ProcessLotteryFormula");
        Log::info("🔹 Batch ID: {$this->batchId}, Từ {$this->startDate} đến {$this->endDate}");
        Log::info("🔹 Formula IDs: " . json_encode($this->formulaIds));

        try {
            // Gọi service để xử lý batch
            $result = $formulaService->processBatchFormulas(
                $this->batchId,
                $this->startDate,
                $this->endDate,
                $this->formulaIds
            );

            // Lưu checkpoint vào cache
            Cache::put("formula_checkpoint_{$this->batchId}", [
                'processed_at' => Carbon::now(),
                'formula_count' => count($this->formulaIds),
                'result_count' => $result['processed_count'] ?? 0,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate
            ], now()->addDays(7));

            Log::info("✅ Hoàn thành xử lý job ProcessLotteryFormula", [$result]);

        } catch (Exception $e) {
            Log::error("⛔ Lỗi trong job ProcessLotteryFormula: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
