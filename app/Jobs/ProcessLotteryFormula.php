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
use function Symfony\Component\Mime\Test\Constraint\toString;

class ProcessLotteryFormula implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $startDate;
    protected $endDate;

    public function __construct($batchId, $startDate = null, $endDate = null)
    {
        $this->batchId = $batchId;
        $this->startDate = $startDate ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $this->endDate = $endDate ?? Carbon::now()->format('Y-m-d');
    }

    public function handle(LotteryFormulaService $formulaService)
    {
        Log::info("🔹 Bắt đầu xử lý job: " . get_class($this));
        Log::info("🔹 Bắt đầu xử lý batch ID: {$this->batchId} từ {$this->startDate} đến {$this->endDate}");
        try {
            // Xử lý logic của job
            // Lấy dữ liệu kết quả xổ số trong khoảng thời gian
            $results = LotteryResult::whereBetween('draw_date', [$this->startDate, $this->endDate])
                ->orderBy('draw_date')
                ->get();
            Log::info("📌 Số lượng kết quả xổ số cần xử lý: " . $results->count());

            // Lấy tối đa 100 bản ghi chưa xử lý đầy đủ từ LotteryFormula
            $cauLos = LotteryFormula::where('is_processed', false)
                ->limit(100)
                ->get();

            if ($cauLos->isEmpty()) {
                Log::warning("⚠ Không có cầu lô nào trong LotteryFormula, chuyển sang lấy từ LotteryFormulaMeta...");

                // Tìm ID lớn nhất của formula_meta_id trong LotteryFormula để tránh lặp lại
                $lastFormulaMetaId = LotteryFormula::max('formula_meta_id') ?? 0;

                // Chỉ lấy những bản ghi có ID lớn hơn ID lớn nhất đã sử dụng
                $metaFormulas = LotteryFormulaMeta::where('id', '>', $lastFormulaMetaId)
                    ->limit(100)
                    ->get();

                if ($metaFormulas->isEmpty()) {
                    Log::warning("⛔ Không có dữ liệu mới trong LotteryFormulaMeta. Kết thúc xử lý.");
                    return;
                }

                foreach ($metaFormulas as $meta) {
                    Log::info("📌 Tạo cầu lô mới từ meta ID: {$meta->id}");

                    $newFormula = new LotteryFormula();
                    $newFormula->formula_meta_id = $meta->id;
                    $newFormula->combination_type = $meta->combination_type;
                    $newFormula->is_processed = false;
                    $newFormula->processed_days = 0;
                    $newFormula->last_processed_date = null;
                    $newFormula->processing_status = 'pending';
                    $newFormula->save();

                    $cauLos->push($newFormula);
                }

                Log::info("✅ Đã tạo mới " . $cauLos->count() . " bản ghi từ LotteryFormulaMeta.");
            }

            // Xử lý từng cầu lô
            foreach ($cauLos as $cauLo) {
                try{
                    Log::info("🔄 Bắt đầu xử lý cầu lô ID: {$cauLo->id}");

                    foreach ($results as $result) {
                        Log::info("📊 Đang tính toán kết quả cho cầu lô ID: {$cauLo->id} với ngày: {$result->draw_date}");
                        $formulaService->calculateResults($cauLo->id, $result->draw_date);
                    }

                    // Cập nhật trạng thái đã xử lý
                    $cauLo->is_processed = true;
                    $cauLo->last_processed_date = Carbon::now();
                    $cauLo->save();
                    Log::info("✅ Hoàn thành xử lý cầu lô ID: {$cauLo->id}");
                }catch (NotPositionResult $e){
                    Log::error($e->getMessage(), ['error' => $e]);
                }
            }

            // Lưu checkpoint vào cache
            Cache::put("formula_checkpoint_{$this->batchId}", [
                'processed_at' => Carbon::now(),
                'cau_count' => $cauLos->count(),
                'result_count' => $results->count()
            ]);
            Log::info("📝 Đã lưu checkpoint vào cache với batch ID: {$this->batchId}");
        }
        catch (NotPositionResult $e) {
            Log::error("ví trí lỗi");
        }
        catch (Exception $e) {
            Log::error("⛔ Lỗi trong job: " . $e->getMessage(), [
                'exception' => $e, // Đính kèm exception vào log
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(), // Stack trace
            ]);
        }

    }
}
