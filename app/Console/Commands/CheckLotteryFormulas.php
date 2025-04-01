<?php
namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryFormulaMeta;
use App\Models\LotteryFormula;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas 
                           {--days=3 : Number of days to check}
                           {--start-date= : Optional start date in Y-m-d format}
                           {--max-formula-batch=2 : Max number of formulas per batch}
                           {--partial : Process only partial status formulas}';

    protected $description = 'Check lottery formulas against results';

    public function handle()
    {
        $days = (int) $this->option('days');
        $userStartDate = $this->option('start-date');
        $maxFormulaBatch = (int) $this->option('max-formula-batch');
        $batchId = uniqid('formula_check_');

        try {
            // Xác định ngày bắt đầu
            if ($userStartDate) {
                $startDate = Carbon::parse($userStartDate)->format('Y-m-d');
            } else {
                $startDate = Carbon::now()->subDays($days - 1)->format('Y-m-d');
            }

//            // Lấy và tạo các formula chưa được xử lý
//            $this->prepareFormulas($maxFormulaBatch);

            // Tính toán batch xử lý
            $batchSize = 100;
            $totalBatches = ceil($days / $batchSize);

            for ($batch = 0; $batch < $totalBatches; $batch++) {
                $batchStartDate = Carbon::parse($startDate)->addDays($batch * $batchSize)->format('Y-m-d');
                $batchEndDate = Carbon::parse($batchStartDate)
                    ->addDays(min($batchSize, $days - $batch * $batchSize) - 1)
                    ->format('Y-m-d');

                // Lấy các formula chưa xử lý
                $formulas = $this->getUnprocessedFormulas($maxFormulaBatch);

                if ($formulas->isEmpty()) {
                    Log::warning("⚠ Không có formula nào để xử lý trong batch {$batch}.");
                    continue;
                }

                Log::info("🔍 Dispatching job for batch #{$batch} ({$batchStartDate} - {$batchEndDate})");
                Log::info("📊 Số lượng formula trong batch: " . $formulas->count());

                // Dispatch job với danh sách formula
                ProcessLotteryFormula::dispatch(
                    $batchId,
                    $batchStartDate,
                    $batchEndDate,
                    $formulas->pluck('id')->toArray()
                );
            }

            $this->info("✅ Dispatched {$totalBatches} batch jobs successfully.");
            return 0;

        } catch (Exception $e) {
            $this->error('⛔ Error: ' . $e->getMessage());
            Log::error('Error in CheckLotteryFormulas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Chuẩn bị các formula chưa được xử lý từ LotteryFormulaMeta
     */
    private function prepareFormulas($maxFormulaBatch)
    {
        // Tìm ID lớn nhất của formula_meta_id trong LotteryFormula
        $lastFormulaMetaId = LotteryFormula::max('formula_meta_id') ?? 0;

        // Lấy các formula meta mới chưa được xử lý
        $metaFormulas = LotteryFormulaMeta::where('id', '>', $lastFormulaMetaId)
            ->limit($maxFormulaBatch)
            ->get();

        foreach ($metaFormulas as $meta) {
            // Kiểm tra xem formula đã tồn tại chưa
            $existingFormula = LotteryFormula::where('formula_meta_id', $meta->id)->first();

            if (!$existingFormula) {
                $newFormula = new LotteryFormula();
                $newFormula->formula_meta_id = $meta->id;
                $newFormula->combination_type = $meta->combination_type;
                $newFormula->is_processed = false;
                $newFormula->processed_days = 0;
                $newFormula->last_processed_date = null;
                $newFormula->processing_status = 'pending';
                $newFormula->save();
            }
        }
    }

    /**
     * Lấy các formula chưa được xử lý, ưu tiên theo rate
     */
    private function getUnprocessedFormulas($limit)
    {
        $query = LotteryFormula::query();

        // Xử lý theo option partial
        if ($this->option('partial')) {
            // Chỉ lấy các formula có trạng thái partial
            $query->where('processing_status', 'partial');
        } else {
            // Lấy các formula chưa được xử lý
            $query->where('is_processed', false);
        }

        return $query->orderBy('processed_days')  // Ưu tiên các formula ít được xử lý
            ->orderBy('last_processed_date', 'asc')  // Ưu tiên các formula lâu không xử lý
            ->limit($limit)
            ->get();
    }
}
