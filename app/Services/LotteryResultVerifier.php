<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;

class LotteryResultVerifier
{
    protected $combinationProcessor;
    protected $batchSize = 100; // Số lượng cầu xử lý mỗi lần

    public function __construct(CombinationProcessor $combinationProcessor)
    {
        $this->combinationProcessor = $combinationProcessor;
    }

    /**
     * Duyệt kết quả cho các cầu chưa được xác minh
     *
     * @param string $date Ngày cần kiểm tra kết quả (Y-m-d)
     * @param array $results Kết quả xổ số của ngày
     * @return array Thống kê kết quả
     */
    public function verifyResults($date, $results)
    {
        // Thiết lập kết quả cho combinationProcessor
        $this->combinationProcessor = new CombinationProcessor($results);

        $stats = [
            'total_processed' => 0,
            'hits' => 0,
            'misses' => 0,
            'errors' => 0
        ];

        // Lấy các cầu chưa được xác minh cho ngày hiện tại
        $unverifiedCombinations = $this->getUnverifiedCombinations($date);

        foreach ($unverifiedCombinations as $cau) {
            $stats['total_processed']++;

            try {
                // Xử lý cầu và kiểm tra với kết quả
                $formulaStructure = json_decode($cau->formula_structure, true);
                $predictedNumber = $this->combinationProcessor->processLotteryCombination($formulaStructure);

                // Kiểm tra kết quả
                $isHit = $this->checkIfHit($predictedNumber, $results);

                // Cập nhật trạng thái
                $this->updateCombinationStatus($cau->id, $date, $isHit);

                if ($isHit) {
                    $stats['hits']++;
                } else {
                    $stats['misses']++;
                }
            } catch (Exception $e) {
                $stats['errors']++;
                // Log lỗi
                Log::error("Error verifying combination ID {$cau->id}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Lấy danh sách các cầu chưa được xác minh cho ngày cụ thể
     */
    protected function getUnverifiedCombinations($date)
    {
        return DB::table('lottery_cau')
            ->select('lottery_cau.*', 'lottery_cau_meta.formula_structure')
            ->join('lottery_cau_meta', 'lottery_cau.formula_meta_id', '=', 'lottery_cau_meta.id')
            ->where(function ($query) use ($date) {
                $query->where('lottery_cau.is_verified', false)
                    ->orWhere('lottery_cau.last_date_verified', '<', $date);
            })
            ->orderBy('lottery_cau.id')
            ->limit($this->batchSize)
            ->get();
    }

    /**
     * Kiểm tra xem số dự đoán có trúng với kết quả không
     */
    protected function checkIfHit($predictedNumber, $results)
    {
        // Nếu không có kết quả dự đoán
        if (!$predictedNumber) {
            return false;
        }

        // Kiểm tra trong tất cả các giải
        foreach ($results as $prize => $numbers) {
            if (is_array($numbers)) {
                foreach ($numbers as $number) {
                    // Kiểm tra xem số dự đoán có xuất hiện trong kết quả hay không
                    if (strpos($number, $predictedNumber) !== false) {
                        return true;
                    }
                }
            } else {
                if (strpos($numbers, $predictedNumber) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cập nhật trạng thái cầu sau khi kiểm tra
     */
    protected function updateCombinationStatus($cauId, $date, $isHit)
    {
        $updateData = [
            'is_verified' => true,
            'last_date_verified' => $date,
            'updated_at' => Carbon::now()
        ];

        if ($isHit) {
            $updateData['hit_count'] = DB::raw('hit_count + 1');
            // Có thể thêm hit_date vào một bảng riêng để lưu lịch sử
        } else {
            $updateData['miss_count'] = DB::raw('miss_count + 1');
        }

        DB::table('lottery_cau')
            ->where('id', $cauId)
            ->update($updateData);

        // Nếu trúng, lưu thêm vào bảng lịch sử
        if ($isHit) {
            DB::table('lottery_cau_hit_history')->insert([
                'cau_id' => $cauId,
                'hit_date' => $date,
                'created_at' => Carbon::now()
            ]);
        }
    }

    /**
     * Xử lý hàng loạt cho nhiều ngày
     *
     * @param string $startDate Ngày bắt đầu (Y-m-d)
     * @param string $endDate Ngày kết thúc (Y-m-d)
     * @return array Thống kê tổng hợp
     */
    public function batchVerifyResults($startDate, $endDate)
    {
        $currentDate = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);

        $totalStats = [
            'days_processed' => 0,
            'total_processed' => 0,
            'total_hits' => 0,
            'total_misses' => 0,
            'total_errors' => 0,
            'daily_stats' => []
        ];

        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');

            // Lấy kết quả xổ số cho ngày hiện tại
            $results = $this->getLotteryResults($dateStr);

            if ($results) {
                $stats = $this->verifyResults($dateStr, $results);

                $totalStats['days_processed']++;
                $totalStats['total_processed'] += $stats['total_processed'];
                $totalStats['total_hits'] += $stats['hits'];
                $totalStats['total_misses'] += $stats['misses'];
                $totalStats['total_errors'] += $stats['errors'];

                $totalStats['daily_stats'][$dateStr] = $stats;
            }

            $currentDate->addDay();
        }

        return $totalStats;
    }

    /**
     * Lấy kết quả xổ số từ database cho ngày cụ thể
     */
    protected function getLotteryResults($date)
    {
        // Lấy kết quả từ bảng lưu kết quả xổ số theo ngày
        $result = DB::table('lottery_results')
            ->where('result_date', $date)
            ->first();

        if (!$result) {
            return null;
        }

        // Chuyển đổi kết quả từ database thành định dạng sử dụng được
        return json_decode($result->result_data, true);
    }

    /**
     * Đặt kích thước batch xử lý
     */
    public function setBatchSize($size)
    {
        $this->batchSize = $size;
        return $this;
    }
}
