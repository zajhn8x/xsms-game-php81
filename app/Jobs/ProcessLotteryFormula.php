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
            // Lấy các formula cần xử lý
            $cauLos = LotteryFormula::whereIn('id', $this->formulaIds)
                ->where('is_processed', false)
                ->get();

            Log::info("📊 Số lượng cầu lô chưa xử lý: " . $cauLos->count());

            // Lấy dữ liệu kết quả xổ số trong khoảng thời gian
            $results = LotteryResult::whereBetween('draw_date', [$this->startDate, $this->endDate])
                ->orderBy('draw_date')
                ->get();

            Log::info("📌 Số lượng kết quả xổ số: " . $results->count());

            // Xử lý từng cầu lô
            foreach ($cauLos as $cauLo) {
                try {
                    Log::info("🔄 Bắt đầu xử lý cầu lô ID: {$cauLo->id}");

                    $processDays = 0;
                    $lastDay = '';
                    foreach ($results as $result) {
//                        Log::info("📊 Tính toán kết quả cho cầu lô ID: {$cauLo->id} với ngày: {$result->draw_date}");

                        $formulaService->calculateResults($cauLo->id, $result->draw_date);
                        $processDays++;
                        $lastDay = $result->draw_date;
                    }

                    // Cập nhật trạng thái đã xử lý
                    //$cauLo->is_processed = $processDays == $results->count();
                    $cauLo->processed_days += $processDays;
                    $cauLo->last_processed_date = $lastDay;
                    $cauLo->processing_status = $cauLo->is_processed ? 'completed' : 'partial';
                    $cauLo->save();

                    Log::info("✅ Hoàn thành xử lý cầu lô ID: {$cauLo->id}. Số ngày xử lý: {$processDays}");

                } catch (NotPositionResult $e) {
                    Log::error("Lỗi vị trí tại cầu lô ID {$cauLo->id}: " . $e->getMessage(), [
                        'formula_id' => $cauLo->id,
                        'error' => $e
                    ]);

                    // Cập nhật trạng thái lỗi
                    $cauLo->processing_status = 'error';
                    $cauLo->save();
                }
            }

            // Lưu checkpoint vào cache
            Cache::put("formula_checkpoint_{$this->batchId}", [
                'processed_at' => Carbon::now(),
                'formula_count' => $cauLos->count(),
                'result_count' => $results->count(),
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
        }
    }
}
