
<?php

namespace App\Services;

use App\Models\LotteryCauLo;
use App\Models\LotteryCauLoHit;
use App\Models\LotteryResult;
use Carbon\Carbon;

class LotteryFormulaService
{
    /**
     * Tính toán kết quả cho công thức cầu lô
     */
    public function calculateResults($cauLoId, $date) 
    {
        $cauLo = LotteryCauLo::with('formula')->findOrFail($cauLoId);
        $result = LotteryResult::where('draw_date', $date)->first();

        if (!$result || !$cauLo) {
            return null;
        }

        // Tính toán số trúng dựa trên kết quả xổ số
        $loArray = $result->lo_array ?? [];
        $soTrung = $this->checkHit($cauLo, $loArray);
        
        if ($soTrung) {
            // Lưu kết quả trúng vào LotteryCauLoHit
            LotteryCauLoHit::create([
                'cau_lo_id' => $cauLo->id,
                'ngay' => $date,
                'so_trung' => $soTrung
            ]);
        }

        return [
            'cau_lo_id' => $cauLo->id,
            'formula_name' => $cauLo->formula->formula_name,
            'draw_date' => $date,
            'so_trung' => $soTrung,
            'result_data' => json_encode([
                'stats' => [
                    'total_hits' => $cauLo->getTotalHitsAttribute(),
                    'hit_rate' => $cauLo->getHitRateAttribute()
                ]
            ])
        ];
    }

    /**
     * Kiểm tra số trúng
     */
    protected function checkHit($cauLo, $loArray)
    {
        // Logic kiểm tra số trúng dựa trên công thức
        // Trả về số trúng nếu có, null nếu không trúng
        return null; 
    }

    /**
     * Xử lý một loạt ngày cho công thức
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
     * Lưu kết quả đã xử lý
     */
    public function saveProcessedResults($cauLoId, $processedData) 
    {
        $cauLo = LotteryCauLo::findOrFail($cauLoId);
        $cauLo->is_processed = true;
        $cauLo->last_processed_date = Carbon::now();
        $cauLo->result_data = array_merge(
            $cauLo->result_data ?? [],
            ['processed_results' => $processedData]
        );
        $cauLo->save();
    }
}
