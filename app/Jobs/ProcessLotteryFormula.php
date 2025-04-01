<?php
namespace App\Jobs;

use App\Exceptions\Lottery\NotPositionResult;
use App\Models\LotteryFormulaMeta;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LotteryResult;
use App\Models\LotteryFormula;
use App\Services\LotteryFormulaService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_encode;

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
        $this->endDate = $endDate ?? Carbon::now()->format('Y-m-d');
        $this->formulaIds = $formulaIds;
    }

    public function handle(LotteryFormulaService $formulaService)
    {
        Log::info("🔹 Bắt đầu xử lý job: " . get_class($this));
        Log::info("🔹 Batch ID: {$this->batchId}, Từ {$this->startDate} đến {$this->endDate}");
        Log::info("🔹 Số lượng formula IDs: " . count($this->formulaIds) . "=>" . json_encode($this->formulaIds));

        try {
            // Lấy formulas cần xử lý
            $formulas = LotteryFormula::whereIn('id', $this->formulaIds)->get();
            
            foreach ($formulas as $formula) {
                $startDate = $formula->processing_status === 'partial' 
                    ? $formula->last_processed_date->addDay()->format('Y-m-d')
                    : $this->startDate;
                    
                // Gọi service để xử lý batch cho từng formula
                $formulaService->processBatchFormulas(
                    $this->batchId,
                    $startDate,
                    $this->endDate,
                    [$formula->id]
                );
            }

            // Lưu checkpoint vào cache
            Cache::put("formula_checkpoint_{$this->batchId}", [
                'processed_at' => Carbon::now(),
                'formula_count' => count($this->formulaIds), // Assuming processBatchFormulas handles this internally.
                'result_count' => LotteryResult::whereBetween('draw_date', [$this->startDate, $this->endDate])->count(),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate
            ], now()->addDays(7)); // Lưu checkpoint trong 7 ngày

            Log::info("📝 Đã lưu checkpoint vào cache với batch ID: {$this->batchId}");

        } catch (Exception $e) {
            Log::error("⛔ Lỗi trong job: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw the exception to be handled by the queue system.
        }
    }
}