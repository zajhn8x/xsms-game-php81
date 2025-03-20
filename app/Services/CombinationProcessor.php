<?php

namespace App\Services;

class CombinationProcessor
{
    protected $config;
    protected $positionsData;

    public function __construct(array $resultsData)
    {
        $this->config = config('xsmb');
        $this->positionsData = $this->mapPositionsData($resultsData);
    }

    /**
     * Ánh xạ dữ liệu kết quả xổ số vào các vị trí
     */
    protected function mapPositionsData(array $resultsData)
    {
        $mappedData = [];

        // Ví dụ cách ánh xạ, cần điều chỉnh theo cấu trúc dữ liệu thực tế
        foreach ($resultsData as $prizeKey => $prizeData) {
            // Giải đặc biệt
            if ($prizeKey === 'special') {
                $digits = str_split($prizeData);
                for ($i = 0; $i < count($digits); $i++) {
                    $posKey = "GDB-1-" . ($i + 1);
                    $mappedData[$posKey] = $digits[$i];
                }
            }
            // Các giải khác
            else {
                $prizeLevel = substr($prizeKey, 5); // Lấy số giải (1-7)

                if (is_array($prizeData)) {
                    foreach ($prizeData as $prizeIdx => $number) {
                        $digits = str_split($number);
                        for ($i = 0; $i < count($digits); $i++) {
                            $posKey = "G{$prizeLevel}-" . ($prizeIdx + 1) . "-" . ($i + 1);
                            $mappedData[$posKey] = $digits[$i];
                        }
                    }
                } else {
                    $digits = str_split($prizeData);
                    for ($i = 0; $i < count($digits); $i++) {
                        $posKey = "G{$prizeLevel}-1-" . ($i + 1);
                        $mappedData[$posKey] = $digits[$i];
                    }
                }
            }
        }

        return $mappedData;
    }

    /**
     * Xử lý ghép cầu lô theo formula_structure
     */
    public function processLotteryCombination($formulaStructure)
    {
        $type = $formulaStructure['type'] ?? 'pair';
        $positions = $formulaStructure['positions'] ?? [];

        switch ($type) {
            case 'single':
                return $this->processSingleCombination($positions[0] ?? null);

            case 'pair':
                return $this->processPairCombination(
                    $positions[0] ?? null,
                    $positions[1] ?? null
                );

            case 'multi':
                return $this->processMultiCombination($positions);

            case 'dynamic':
                return $this->processDynamicCombination($formulaStructure);

            default:
                return null;
        }
    }

    /**
     * Xử lý cầu lô từ một vị trí duy nhất
     */
    protected function processSingleCombination($position)
    {
        if (!$position || !isset($this->positionsData[$position])) {
            return null;
        }

        return $this->positionsData[$position];
    }

    /**
     * Xử lý ghép cầu lô từ hai vị trí
     */
    protected function processPairCombination($position1, $position2)
    {
        if (!$position1 || !$position2 ||
            !isset($this->positionsData[$position1]) ||
            !isset($this->positionsData[$position2])) {
            return null;
        }

        $digit1 = $this->positionsData[$position1];
        $digit2 = $this->positionsData[$position2];

        return $digit1 . $digit2;
    }

    /**
     * Xử lý ghép cầu lô từ nhiều vị trí
     */
    protected function processMultiCombination(array $positions)
    {
        if (empty($positions)) {
            return null;
        }

        $result = '';
        foreach ($positions as $position) {
            if (!isset($this->positionsData[$position])) {
                return null; // Nếu thiếu bất kỳ vị trí nào, trả về null
            }
            $result .= $this->positionsData[$position];
        }

        return $result;
    }

    /**
     * Xử lý ghép cầu lô động (máy học)
     */
    protected function processDynamicCombination($formulaStructure)
    {
        // Triển khai thuật toán machine learning để xác định vị trí tối ưu
        // Đây là phần triển khai đơn giản, cần được mở rộng
        $algorithm = $formulaStructure['algorithm'] ?? 'frequency';
        $historyData = $formulaStructure['history_data'] ?? [];

        // Mẫu triển khai dựa trên tần suất xuất hiện
        if ($algorithm === 'frequency' && !empty($historyData)) {
            // Logic tính toán vị trí tối ưu dựa trên tần suất
            // ...

            // Giả định đã tìm được các vị trí tối ưu
            $optimalPositions = ['G7-3-2', 'G7-4-2'];

            return $this->processPairCombination(
                $optimalPositions[0],
                $optimalPositions[1]
            );
        }

        return null;
    }

    /**
     * Lấy danh sách tất cả các vị trí có sẵn
     */
    public function getAllAvailablePositions()
    {
        return $this->config['positions'] ?? [];
    }

    /**
     * Lấy danh sách các công thức mẫu
     */
    public function getPredefinedFormulas()
    {
        return $this->config['predefined_formulas'] ?? [];
    }
}
