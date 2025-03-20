<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LotteryCombinationGenerator
{
    protected $config;
    protected $positions;

    public function __construct()
    {
        $this->config = config('xsmb');
        $this->positions = $this->config['positions'] ?? [];
    }

    /**
     * Tạo 1000 cầu lô kết hợp ngẫu nhiên
     *
     * @return array
     */
    public function generateRandomCombinations($count = 1000)
    {
        $combinations = [];
        $combinationTypes = array_keys($this->config['combination_types'] ?? []);

        for ($i = 0; $i < $count; $i++) {
            // Chọn ngẫu nhiên loại combination
            $type = $combinationTypes[array_rand($combinationTypes)];

            // Tạo công thức dựa vào loại
            $formula = $this->generateFormulaByType($type);

            $combinations[] = [
                'name' => 'Cầu ' . ($i + 1),
                'combination_type' => $type,
                'formula_structure' => json_encode($formula),
                'formula_note' => $this->generateNoteFromFormula($type, $formula),
                'is_verified' => false, // Cờ chưa duyệt qua kết quả
                'last_date_verified' => null, // Ngày duyệt qua gần nhất
                'hit_count' => 0, // Số lần trúng
                'miss_count' => 0, // Số lần trượt
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        return $combinations;
    }

    /**
     * Tạo cấu trúc công thức dựa theo loại
     *
     * @param string $type
     * @return array
     */
    protected function generateFormulaByType($type)
    {
        $allPositions = $this->flattenPositions();

        switch ($type) {
            case 'single':
                return [
                    'type' => 'single',
                    'positions' => [$allPositions[array_rand($allPositions)]],
                    'weight' => mt_rand(1, 5) / 5, // Trọng số ngẫu nhiên từ 0.2-1.0
                ];

            case 'pair':
                $pos1 = $allPositions[array_rand($allPositions)];
                $pos2 = $allPositions[array_rand($allPositions)];

                // Đảm bảo 2 vị trí khác nhau
                while ($pos2 === $pos1) {
                    $pos2 = $allPositions[array_rand($allPositions)];
                }

                return [
                    'type' => 'pair',
                    'positions' => [$pos1, $pos2],
                    'weight' => mt_rand(1, 5) / 5,
                ];

            case 'multi':
                $numPositions = mt_rand(3, 5);
                $selectedPositions = [];

                for ($i = 0; $i < $numPositions; $i++) {
                    $newPos = $allPositions[array_rand($allPositions)];

                    // Đảm bảo không trùng lặp
                    while (in_array($newPos, $selectedPositions)) {
                        $newPos = $allPositions[array_rand($allPositions)];
                    }

                    $selectedPositions[] = $newPos;
                }

                return [
                    'type' => 'multi',
                    'positions' => $selectedPositions,
                    'weight' => mt_rand(1, 5) / 5,
                ];

            case 'dynamic':
                return [
                    'type' => 'dynamic',
                    'algorithm' => $this->getRandomAlgorithm(),
                    'parameters' => [
                        'lookback_days' => mt_rand(10, 30),
                        'confidence_threshold' => mt_rand(60, 95) / 100,
                        'min_frequency' => mt_rand(3, 8),
                    ],
                    'weight' => mt_rand(1, 5) / 5,
                ];

            default:
                return [
                    'type' => 'pair',
                    'positions' => [
                        $allPositions[array_rand($allPositions)],
                        $allPositions[array_rand($allPositions)]
                    ],
                    'weight' => 1.0,
                ];
        }
    }

    /**
     * Chọn thuật toán ngẫu nhiên cho dạng dynamic
     */
    protected function getRandomAlgorithm()
    {
        $algorithms = ['frequency', 'pattern', 'markov', 'neural_network', 'regression'];
        return $algorithms[array_rand($algorithms)];
    }

    /**
     * Chuyển đổi cấu trúc nested array của positions thành mảng phẳng
     */
    protected function flattenPositions()
    {
        $flattened = [];

        foreach ($this->positions as $prizePositions) {
            foreach ($prizePositions as $position) {
                $flattened[] = $position;
            }
        }

        return $flattened;
    }

    /**
     * Tạo mô tả từ công thức
     */
    protected function generateNoteFromFormula($type, $formula)
    {
        switch ($type) {
            case 'single':
                return "Cầu đơn từ vị trí {$formula['positions'][0]}";

            case 'pair':
                return "Cầu ghép từ vị trí {$formula['positions'][0]} và {$formula['positions'][1]}";

            case 'multi':
                return "Cầu ghép từ " . count($formula['positions']) . " vị trí: " . implode(', ', $formula['positions']);

            case 'dynamic':
                return "Cầu tự động xác định bằng thuật toán {$formula['algorithm']} với lookback {$formula['parameters']['lookback_days']} ngày";

            default:
                return "Cầu tổng hợp";
        }
    }

    /**
     * Lưu danh sách cầu vào database
     */
    public function saveCombinationsToDatabase($combinations)
    {
        // Tạo các bản ghi trong bảng lottery_cau_meta
        $metaIds = [];
        foreach ($combinations as $combination) {
            $metaId = DB::table('lottery_cau_meta')->insertGetId([
                'formula_name' => $combination['name'],
                'formula_note' => $combination['formula_note'],
                'formula_structure' => $combination['formula_structure'],
                'combination_type' => $combination['combination_type'],
                'created_at' => $combination['created_at'],
                'updated_at' => $combination['updated_at'],
            ]);

            $metaIds[] = $metaId;
        }

        return $metaIds;
    }
}
