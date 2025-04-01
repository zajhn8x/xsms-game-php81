<?php

namespace App\Services;

use App\Models\FormulaHit;
use App\Models\LotteryFormula;
use App\Models\LotteryResult;
use App\Exceptions\Lottery\NotPositionResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class LotteryFormulaService
{
    protected $indexResultsService;

    public function __construct(LotteryIndexResultsService $indexResultsService)
    {
        $this->indexResultsService = $indexResultsService;
    }

    /**
     * Xử lý công thức cầu lô theo lô
     */
    public function calculateResults($formulaId, $date)
    {
        // Lấy công thức và kết quả xổ số
        $formula = LotteryFormula::with('formula')->findOrFail($formulaId);
        $result = LotteryResult::where('draw_date', $date)->first();
        $nextDay = Carbon::parse($date)->addDay()->format('Y-m-d');
        $nextDayResult = LotteryResult::where('draw_date', $nextDay)->first();

        if (!$result || !$nextDayResult || !$formula->formula) {
            return null;
        }

        // Lấy vị trí từ công thức
        $positions = $formula->formula->positions ?? [];
        if (empty($positions)) {
            throw new NotPositionResult("Không tìm thấy vị trí trong công thức");
        }

        // Lấy giá trị từ vị trí
        $positionValues = $this->indexResultsService->getPositionValue($date, $positions);

        // Ghép số và kiểm tra trúng
        $numbers = $this->combineNumbers($positionValues);
        $hits = $this->checkHit($numbers, $nextDayResult->lo_array ?? []);

        if (empty($hits)) {
            return null;
        }

        // Lưu kết quả trúng
        foreach ($hits as $hit) {
            FormulaHit::create([
                'cau_lo_id' => $formula->id,
                'ngay' => $nextDay,
                'so_trung' => $hit
            ]);
        }

        return [
            'formula_id' => $formula->id,
            'date' => $date,
            'next_date' => $nextDay,
            'hits' => $hits
        ];
    }

    /**
     * Ghép 2 số thành các cặp số
     */
    protected function combineNumbers(array $numbers)
    {
        if (count($numbers) !== 2) {
            throw new NotPositionResult("Cần đúng 2 số để ghép cặp");
        }

        list($a, $b) = $numbers;
        return [
            intval($a . $b),
            intval($b . $a)
        ];
    }

    /**
     * Kiểm tra số trúng
     */
    protected function checkHit($numbers, $resultNumbers)
    {
        $hits = [];
        foreach ($numbers as $number) {
            if (in_array($number, $resultNumbers)) {
                $hits[] = $number;
            }
        }
        return $hits;
    }

    /**
     * Xử lý nhiều công thức theo batch
     */
    public function processBatchFormulas($batchId, $startDate, $endDate, array $formulaIds)
    {
        Log::info("🔹 Bắt đầu xử lý batch formulas");
        $processedCount = 0;
        $errorCount = 0;

        try {
            // Lấy danh sách công thức cần xử lý
            $formulas = LotteryFormula::whereIn('id', $formulaIds)->get();

            // Lấy danh sách ngày cần xử lý
            $dates = LotteryResult::whereBetween('draw_date', [$startDate, $endDate])
                ->orderBy('draw_date')
                ->pluck('draw_date')
                ->toArray();

            foreach ($formulas as $formula) {
                try {
                    // Xác định ngày bắt đầu xử lý
                    $processStartDate = $formula->processing_status === 'partial'
                        ? Carbon::parse($formula->last_processed_date)->addDay()->format('Y-m-d')
                        : $startDate;

                    // Lọc những ngày cần xử lý
                    $daysToProcess = array_filter($dates, function($date) use ($processStartDate) {
                        return $date >= $processStartDate;
                    });

                    if (empty($daysToProcess)) {
                        continue;
                    }

                    // Xử lý từng ngày
                    foreach ($daysToProcess as $date) {
                        $result = $this->calculateResults($formula->id, $date);
                        if ($result) {
                            $processedCount++;
                        }
                    }

                    // Cập nhật trạng thái công thức
                    $lastProcessedDate = end($daysToProcess);
                    $formula->processed_days += count($daysToProcess);
                    $formula->last_processed_date = $lastProcessedDate;
                    $formula->processing_status = Carbon::parse($lastProcessedDate)->format('Y-m-d') >= $endDate 
                        ? 'completed' 
                        : 'partial';
                    $formula->save();

                } catch (Exception $e) {
                    Log::error("Lỗi xử lý formula {$formula->id}: " . $e->getMessage());
                    $errorCount++;

                    // Đánh dấu formula lỗi
                    $formula->processing_status = 'error';
                    $formula->save();
                }
            }

            return [
                'status' => 'success',
                'processed_count' => $processedCount,
                'error_count' => $errorCount,
                'formula_count' => count($formulas)
            ];

        } catch (Exception $e) {
            Log::error("Lỗi xử lý batch: " . $e->getMessage());
            throw $e;
        }
    }

    public function saveProcessedResults($cauLoId, $processedData)
    {
        $cauLo = LotteryFormula::findOrFail($cauLoId);
        $cauLo->is_processed = true;
        //This line was problematic because $resultNextDay was not in scope.  It is likely this was intended to be the last processed date.  Using $processedData['next_date'] instead
        $cauLo->last_processed_date = $processedData['next_date'];


        // Gộp kết quả mới với dữ liệu cũ (nếu có)
        $cauLo->result_data = array_merge(
            $cauLo->result_data ?? [],
            ['processed_results' => $processedData]
        );

        $cauLo->save();
    }
}