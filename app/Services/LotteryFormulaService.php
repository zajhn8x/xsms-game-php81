<?php

namespace App\Services;

use App\Models\FormulaHit;
use App\Models\LotteryFormula;
use App\Models\LotteryFormulaHit;
use App\Models\LotteryFormulaMeta;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Event\InvalidArgumentException;
use function GuzzleHttp\json_encode;
use App\Exceptions\Lottery\NotPositionResult;

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
        // Lấy kết quả xổ số của ngày hiện tại
        $result = LotteryResult::where('draw_date', $date)->first();

        // Lấy kết quả xổ số của ngày tiếp theo để kiểm tra số trúng
        $nextDay = Carbon::parse($date)->addDay()->format('Y-m-d');
        $resultNextDay = LotteryResult::where('draw_date', $nextDay)->first();
        if (!$result || !$resultNextDay || !$cauLo) {
            return null; // Nếu không có dữ liệu thì thoát sớm
        }

        // Danh sách các số lô của ngày tiếp theo
        $loArrayNextDay = $resultNextDay->lo_array ?? [];

        // Lấy danh sách vị trí từ công thức
        /** @var LotteryFormulaMeta $cauLo->formula */
        $formulaPositions = $cauLo->formula->positions ?? [];
        //Không lấy được vị trí thì return
        if(empty($formulaPositions)) return null; //không lấy được thoát sớm

        // Lấy dữ liệu thống kê theo vị trí từ LotteryIndexResultsService
        $indexResultsService = new LotteryIndexResultsService();
        $cauLoArray = $indexResultsService->getPositionValue($date, $formulaPositions);
        // Kiểm tra số trúng dựa trên dữ liệu vị trí và lô của ngày tiếp theo
        $soTrungs = $this->checkHit($cauLoArray, $loArrayNextDay);
        if(empty($soTrungs)) return null; //không trúng thì thoát sớm.
        foreach ($soTrungs as $soTrung){
            // Lưu kết quả trúng vào bảng LotteryFormulaHit
            FormulaHit::create([
                'cau_lo_id' => $cauLo->id,
                'ngay' => $nextDay,
                'so_trung' => $soTrung
            ]);
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
    function combineNumbers(array $numbers) {
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
    protected function checkHit($cauLoArray, $loArrayNextDay,$sorted = false)
    {
        $result = null;
        $cauLoPairArray = $this->combineNumbers($cauLoArray);
        // Kiểm tra từng số từ công thức có trùng khớp với lô ngày tiếp theo không
        foreach ($cauLoPairArray as $number) {
            if (in_array($number, $loArrayNextDay)) {
                $result[]  = $number; // Trả về số trúng đầu tiên tìm thấy
            }
        }
        if($result) return $result;
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
    public function saveProcessedResults($cauLoId, $processedData)
    {
        $cauLo = LotteryFormula::findOrFail($cauLoId);
        $cauLo->is_processed = true;
        $cauLo->last_processed_date = Carbon::now();

        // Gộp kết quả mới với dữ liệu cũ (nếu có)
        $cauLo->result_data = array_merge(
            $cauLo->result_data ?? [],
            ['processed_results' => $processedData]
        );

        $cauLo->save();
    }
}
