<?php

namespace App\Services;

use App\Models\FormulaHit;
use App\Models\LotteryFormula;
use App\Models\LotteryResult;
use App\Exceptions\Lottery\NotPositionResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class LotteryFormulaService
{
    /**
     * Tính toán kết quả cho công thức cầu lô
     *
     * @param int $cauLoId ID của công thức cầu lô
     * @param string $date Ngày quay số (định dạng YYYY-MM-DD)
     * @return array|null Trả về dữ liệu kết quả hoặc null nếu không có dữ liệu
     */
    public function calculateResults($cauLoId, $date)
    {
        // Lấy công thức cầu lô kèm theo thông tin liên kết từ bảng formula
        $cauLo = LotteryFormula::with('formula')->findOrFail($cauLoId);
        
        // Lấy kết quả xổ số của ngày hiện tại và ngày tiếp theo
        $result = LotteryResult::where('draw_date', $date)->first();
        $nextDay = Carbon::parse($date)->addDay()->format('Y-m-d');
        $resultNextDay = LotteryResult::where('draw_date', $nextDay)->first();
        
        if (!$result || !$resultNextDay || !$cauLo) {
            return null;
        }

        // Lấy lịch sử hit của công thức này trong 10 ngày gần nhất
        $recentHits = FormulaHit::where('cau_lo_id', $cauLoId)
            ->where('ngay', '<=', $date)
            ->orderBy('ngay', 'desc')
            ->take(10)
            ->get();

        // Danh sách các số lô của ngày tiếp theo
        $loArrayNextDay = $resultNextDay->lo_array ?? [];

        // Lấy danh sách vị trí từ công thức
        /** @var LotteryFormulaMeta $cauLo- >formula */
        $formulaPositions = $cauLo->formula->positions ?? [];
        //Không lấy được vị trí thì return
        if (empty($formulaPositions)) return null; //không lấy được thoát sớm

        // Lấy dữ liệu thống kê theo vị trí từ LotteryIndexResultsService
        $indexResultsService = new LotteryIndexResultsService();
        $cauLoArray = $indexResultsService->getPositionValue($date, $formulaPositions);
        // Kiểm tra số trúng dựa trên dữ liệu vị trí và lô của ngày tiếp theo
        $soTrungs = $this->checkHit($cauLoArray, $loArrayNextDay);
        if (empty($soTrungs)) return null; //không trúng thì thoát sớm.
        foreach ($soTrungs as $soTrung) {
            // Xác định trạng thái hit
            $status = $this->determineHitStatus($recentHits, $soTrung);
            
            // Lưu kết quả trúng với trạng thái
            FormulaHit::firstOrCreate([
                'cau_lo_id' => $cauLo->id,
                'ngay' => $nextDay,
                'so_trung' => $soTrung,
                'status' => $status
            ]);
        }

        private function determineHitStatus($recentHits, $currentNumber) {
            if ($recentHits->isEmpty()) {
                return 0; // Normal hit
            }

            $consecutiveHits = $this->getConsecutiveHits($recentHits);
            $hitCount = count($consecutiveHits);
            
            if ($hitCount >= 3) {
                return 4; // Nhiều hơn 2 nháy
            }

            if ($hitCount == 2) {
                $lastHit = $consecutiveHits[0];
                $prevHit = $consecutiveHits[1];
                
                // Kiểm tra cùng chiều
                if ($this->isSameDirection($lastHit, $prevHit, $currentNumber)) {
                    return 1;
                }
                
                // Kiểm tra 2 nháy 1 số
                if ($this->isSameNumberHit($lastHit, $prevHit)) {
                    return 2;
                }
                
                // Kiểm tra 2 nháy cả cặp
                if ($this->isPairHit($lastHit, $prevHit)) {
                    return 3;
                }
            }
            
            return 0; // Normal hit
        }

        private function getConsecutiveHits($hits) {
            $consecutive = [];
            $prevDate = null;
            
            foreach ($hits as $hit) {
                if ($prevDate === null || Carbon::parse($hit->ngay)->addDay()->format('Y-m-d') == $prevDate) {
                    $consecutive[] = $hit;
                } else {
                    break;
                }
                $prevDate = $hit->ngay;
            }
            
            return $consecutive;
        }

        private function isSameDirection($lastHit, $prevHit, $currentNumber) {
            // Kiểm tra xu hướng tăng/giảm
            $trend = $lastHit->so_trung - $prevHit->so_trung;
            $newTrend = $currentNumber - $lastHit->so_trung;
            return ($trend > 0 && $newTrend > 0) || ($trend < 0 && $newTrend < 0);
        }

        private function isSameNumberHit($lastHit, $prevHit) {
            // Kiểm tra có cùng 1 số không
            return strval($lastHit->so_trung)[0] == strval($prevHit->so_trung)[0] ||
                   strval($lastHit->so_trung)[1] == strval($prevHit->so_trung)[1];
        }

        private function isPairHit($lastHit, $prevHit) {
            // Kiểm tra có phải cặp số không
            return $lastHit->so_trung == strrev($prevHit->so_trung);
        }

        return [
            'cau_lo_id' => $cauLo->id,
            'formula_name' => $cauLo->formula->formula_name,
            'draw_date' => $date,
            'next_draw_date' => $nextDay,
            'so_trung' => json_encode($soTrungs),
            'result_data' => json_encode([
                'stats' => [
                    'total_hits' => $cauLo->getTotalHitsAttribute(),
                    'hit_rate' => $cauLo->getHitRateAttribute()
                ]
            ])
        ];
    }

    /**
     * Ghép 2 số 0-9 thành số có 2 chữ số
     */
    function combineNumbers(array $numbers)
    {
        if (count($numbers) !== 2) {
            throw new NotPositionResult("Mảng phải chứa đúng 2 phần tử.");
        }

        list($a, $b) = $numbers;

        return [
            intval($a . $b), // Ghép theo thứ tự a trước b
            intval($b . $a)  // Ghép theo thứ tự b trước a
        ];
    }

    /**
     * Kiểm tra số trúng dựa trên dữ liệu vị trí và lô ngày tiếp theo
     *
     * @param array $cauLoArray Mảng các số từ công thức đã được xử lý
     * @param array $loArrayNextDay Mảng các số lô của ngày tiếp theo
     * @param boolean $sorted default false trường hợp đặc biệt xét đến thứ tự theo công thức
     * @return array|null Trả về số trúng nếu có, null nếu không có kết quả
     */
    protected function checkHit($cauLoArray, $loArrayNextDay, $sorted = false)
    {
        $result = null;
        $cauLoPairArray = $this->combineNumbers($cauLoArray);
        // Kiểm tra từng số từ công thức có trùng khớp với lô ngày tiếp theo không
        foreach ($cauLoPairArray as $number) {
            if (in_array($number, $loArrayNextDay)) {
                $result[] = $number; // Trả về số trúng đầu tiên tìm thấy
            }
        }
        if ($result) return $result;
        return null; // Không có số nào trúng
    }

    /**
     * Xử lý một loạt ngày cho công thức cầu lô
     *
     * @param int $cauLoId ID công thức cầu lô
     * @param string $startDate Ngày bắt đầu (YYYY-MM-DD)
     * @param string $endDate Ngày kết thúc (YYYY-MM-DD)
     * @return array Danh sách kết quả đã xử lý
     */
    public function processBatch($cauLoId, $startDate, $endDate)
    {
        $results = LotteryResult::whereBetween('draw_date', [$startDate, $endDate])
            ->orderBy('draw_date')
            ->get();

        $processedData = [];

        foreach ($results as $result) {
            $data = $this->calculateResults($cauLoId, $result->draw_date);
            if ($data) {
                $processedData[$result->draw_date] = $data;
            }
        }

        return $processedData;
    }

    /**
     * Lưu kết quả đã xử lý vào bảng LotteryFormula
     *
     * @param int $cauLoId ID công thức cầu lô
     * @param array $processedData Dữ liệu kết quả đã xử lý
     */
    /**
     * Xử lý một loạt công thức trong một khoảng thời gian
     *
     * @param string $batchId ID của batch xử lý
     * @param string $startDate Ngày bắt đầu
     * @param string $endDate Ngày kết thúc
     * @param array $formulaIds Danh sách ID công thức cần xử lý
     */
    public function processBatchFormulas($batchId, $startDate, $endDate, array $formulaIds)
    {
        Log::info("🔹 Bắt đầu xử lý batch formulas");
        Log::info("🔹 Batch ID: {$batchId}, Từ {$startDate} đến {$endDate}");
        Log::info("🔹 Số lượng formula IDs: " . count($formulaIds) . "=>" . json_encode($formulaIds));

        try {
            // Lấy các formula cần xử lý
            $cauLos = LotteryFormula::whereIn('id', $formulaIds)
                ->where('is_processed', false)
                ->get();

            Log::info("📊 Số lượng cầu lô chưa xử lý: " . $cauLos->count());

            // Lấy dữ liệu kết quả xổ số trong khoảng thời gian
            $results = LotteryResult::whereBetween('draw_date', [$startDate, $endDate])
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
                        $this->calculateResults($cauLo->id, $result->draw_date);
                        $processDays++;
                        $lastDay = $result->draw_date;
                    }

                    // Cập nhật trạng thái đã xử lý
                    $cauLo->processed_days += $processDays;
                    $cauLo->last_processed_date = $lastDay ? $lastDay : $cauLo->last_processed_date;
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
            Cache::put("formula_checkpoint_{$batchId}", [
                'processed_at' => Carbon::now(),
                'formula_count' => $cauLos->count(),
                'result_count' => $results->count(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ], now()->addDays(7));

            Log::info("📝 Đã lưu checkpoint vào cache với batch ID: {$batchId}");

        } catch (Exception $e) {
            Log::error("⛔ Lỗi trong quá trình xử lý batch: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function saveProcessedResults($cauLoId, $processedData)
    {
        $cauLo = LotteryFormula::findOrFail($cauLoId);
        $cauLo->is_processed = true;
        $cauLo->last_processed_date = $resultNextDay->draw_date;

        // Gộp kết quả mới với dữ liệu cũ (nếu có)
        $cauLo->result_data = array_merge(
            $cauLo->result_data ?? [],
            ['processed_results' => $processedData]
        );

        $cauLo->save();
    }
}
