<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\LotteryFormula;
use App\Models\LotteryFormulaMetaMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateAndVerifyLotteryCombinations
{
    protected $resultService;

    public function __construct(LotteryResultsService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Tạo mới các cầu lô dựa trên các công thức
     *
     * @param int $count Số lượng cầu tạo mới
     * @return array Thông tin về các cầu đã tạo
     */
    public function generateCombinations($count = 100)
    {
        $generated = 0;
        $formulas = LotteryFormulaMeta::inRandomOrder()->limit($count)->get();

        $caus = [];
        foreach ($formulas as $formula) {
            $cau = new LotteryFormula();
            $cau->combination_type = $formula->combination_type;
            $cau->formula_meta_id = $formula->id;
            $cau->is_processed = false;
            $cau->processed_days = 0;
            $cau->processing_status = 'pending';
            $cau->result_data = json_encode(['history' => [], 'stats' => ['total_hits' => 0, 'hit_rate' => 0]]);
            $cau->save();

            $caus[] = $cau;
            $generated++;
        }

        return [
            'status' => 'success',
            'generated' => $generated,
            'caus' => $caus
        ];
    }

    /**
     * Kiểm tra và cập nhật kết quả cho các cầu lô
     *
     * @param int $limit Số lượng cầu xử lý mỗi lần
     * @return array Kết quả xử lý
     */
    public function verifyAndUpdateCombinations($limit = 100)
    {
        // Lấy các cầu cần xử lý
        $caus = LotteryCau::where(function($query) {
            $query->where('is_processed', false)
                ->orWhere('processing_status', 'in_progress');
        })
            ->orderBy('last_processed_date', 'asc')
            ->limit($limit)
            ->get();

        if ($caus->isEmpty()) {
            return ['status' => 'no_data', 'message' => 'Không có cầu cần xử lý'];
        }

        // Lấy tất cả ngày xổ số
        $drawDates = $this->resultService->getDrawDates();

        $processed = 0;
        $updated = 0;
        $errors = 0;

        foreach ($caus as $cau) {
            try {
                $meta = LotteryFormulaMeta::find($cau->formula_meta_id);
                if (!$meta) {
                    Log::error("Formula meta không tồn tại cho cầu ID: " . $cau->id);
                    $errors++;
                    continue;
                }

                $formulaStructure = json_decode($meta->formula_structure, true);
                if (!$formulaStructure) {
                    Log::error("Formula structure không hợp lệ cho cầu ID: " . $cau->id);
                    $errors++;
                    continue;
                }

                // Lấy dữ liệu kết quả hiện tại
                $resultData = json_decode($cau->result_data, true) ?: ['history' => [], 'stats' => ['total_hits' => 0, 'hit_rate' => 0]];
                $processedHistory = collect($resultData['history'])->pluck('date')->toArray();

                // Lọc ra các ngày chưa được xử lý
                $datesToProcess = array_filter($drawDates, function($date) use ($processedHistory) {
                    return !in_array($date, $processedHistory);
                });

                if (empty($datesToProcess)) {
                    $cau->processing_status = 'completed';
                    $cau->is_processed = true;
                    $cau->save();

                    $processed++;
                    continue;
                }

                $newResults = [];
                $hits = 0;

                // Xử lý từng ngày
                foreach ($datesToProcess as $date) {
                    $positionValues = $this->resultService->getPositionsForDate($date);
                    if (empty($positionValues)) {
                        continue;
                    }

                    $result = $this->processLotteryCombination($formulaStructure, $positionValues);
                    if ($result === null) {
                        continue;
                    }

                    // Kiểm tra kết quả ra lô
                    $hit = $this->checkHit($result);
                    $hits += $hit ? 1 : 0;

                    $newResults[] = [
                        'date' => $date,
                        'result' => $result,
                        'hit' => $hit
                    ];
                }

                // Cập nhật dữ liệu kết quả
                $resultData['history'] = array_merge($resultData['history'], $newResults);
                $totalHits = $resultData['stats']['total_hits'] + $hits;
                $totalProcessed = count($resultData['history']);

                $resultData['stats'] = [
                    'total_hits' => $totalHits,
                    'hit_rate' => $totalProcessed > 0 ? round($totalHits / $totalProcessed * 100, 2) : 0,
                ];

                // Cập nhật trạng thái cầu
                $cau->is_processed = true;
                $cau->processed_days = $totalProcessed;
                $cau->last_processed_date = !empty($datesToProcess) ? reset($datesToProcess) : $cau->last_processed_date;
                $cau->processing_status = count($drawDates) === $totalProcessed ? 'completed' : 'in_progress';
                $cau->result_data = json_encode($resultData);
                $cau->save();

                $updated++;
            } catch (Exception $e) {
                Log::error("Lỗi xử lý cầu ID: " . $cau->id . " - " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'status' => 'success',
            'processed' => $processed,
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    /**
     * Xử lý công thức ghép cầu lô để tạo kết quả
     *
     * @param array $formulaStructure Cấu trúc công thức
     * @param array $positionValues Giá trị các vị trí
     * @return string|null Kết quả ghép (số lô)
     */
    protected function processLotteryCombination($formulaStructure, $positionValues)
    {
        $type = $formulaStructure['type'] ?? 'pair';
        $positions = $formulaStructure['positions'] ?? [];

        switch ($type) {
            case 'single':
                return $this->processSingleCombination($positions[0] ?? null, $positionValues);

            case 'pair':
                return $this->processPairCombination(
                    $positions[0] ?? null,
                    $positions[1] ?? null,
                    $positionValues
                );

            case 'multi':
                return $this->processMultiCombination($positions, $positionValues);

            case 'dynamic':
                return $this->processDynamicCombination($formulaStructure, $positionValues);

            default:
                return null;
        }
    }

    /**
     * Xử lý cầu lô từ một vị trí duy nhất
     */
    protected function processSingleCombination($position, $positionValues)
    {
        if (!$position || !isset($positionValues[$position])) {
            return null;
        }

        return $positionValues[$position];
    }

    /**
     * Xử lý ghép cầu lô từ hai vị trí
     */
    protected function processPairCombination($position1, $position2, $positionValues)
    {
        if (!$position1 || !$position2 ||
            !isset($positionValues[$position1]) ||
            !isset($positionValues[$position2])) {
            return null;
        }

        $digit1 = $positionValues[$position1];
        $digit2 = $positionValues[$position2];

        return $digit1 . $digit2;
    }

    /**
     * Xử lý ghép cầu lô từ nhiều vị trí
     */
    protected function processMultiCombination(array $positions, $positionValues)
    {
        if (empty($positions)) {
            return null;
        }

        $result = '';
        foreach ($positions as $position) {
            if (!isset($positionValues[$position])) {
                return null;
            }
            $result .= $positionValues[$position];
        }

        return $result;
    }

    /**
     * Xử lý ghép cầu lô động (máy học)
     */
    protected function processDynamicCombination($formulaStructure, $positionValues)
    {
        // Phần triển khai thuật toán machine learning
        // Ví dụ đơn giản, chọn 2 vị trí có tần suất cao nhất
        $algorithm = $formulaStructure['algorithm'] ?? 'frequency';

        if ($algorithm === 'frequency') {
            // Trong thực tế, đây sẽ là một thuật toán phức tạp hơn
            // Đây chỉ là mẫu triển khai đơn giản
            $frequencyMap = [];

            // Lấy lịch sử tần suất xuất hiện số từ database
            // ...

            // Chọn 2 vị trí có tần suất cao nhất (mô phỏng)
            $topPositions = ['G7-3-2', 'G7-4-2'];

            return $this->processPairCombination(
                $topPositions[0],
                $topPositions[1],
                $positionValues
            );
        }

        return null;
    }

    /**
     * Kiểm tra kết quả có trúng lô không
     * Trong thực tế, chức năng này có thể phức tạp hơn
     *
     * @param string $result Kết quả ghép
     * @return bool True nếu trúng, false nếu không
     */
    protected function checkHit($result)
    {
        if (strlen($result) !== 2) {
            return false;
        }

        // Trong thực tế, bạn sẽ kiểm tra kết quả với kết quả xổ số thực tế
        // Đây chỉ là mẫu triển khai đơn giản
        // Giả sử 20% cơ hội trúng
        return rand(1, 100) <= 20;
    }
}
