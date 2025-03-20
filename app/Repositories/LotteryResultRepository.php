<?php

namespace App\Repositories;

use App\Models\LotteryResult;
use App\Contracts\Repositories\LotteryResultRepositoryInterface;
use Carbon\Carbon;

class LotteryResultRepository implements LotteryResultRepositoryInterface
{
    public function latest($limit)
    {
        return LotteryResult::orderBy('draw_date', 'desc')
            ->take($limit)
            ->get();
    }

    public function findByDateRange($startDate, $endDate)
    {
        return LotteryResult::whereBetween('draw_date', [
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        ])->get();
    }

    public function create(array $data)
    {
        return LotteryResult::create($data);
    }

    public function update($id, array $data)
    {
        $result = LotteryResult::findOrFail($id);
        $result->update($data);
        return $result;
    }

    public function delete($id)
    {
        return LotteryResult::destroy($id);
    }

    /**
     * Thêm kết quả xổ số vào bảng lottery_results_index
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @param array $positionValues Map các vị trí và giá trị của chúng
     * @return bool Thành công hay thất bại
     */
    public function insertResults($date, array $positionValues)
    {
        try {
            $data = [];
            $now = Carbon::now();

            foreach ($positionValues as $position => $value) {
                $data[] = [
                    'draw_date' => $date,
                    'position' => $position,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // Sử dụng transaction để đảm bảo tính nhất quán
            DB::beginTransaction();

            // Xóa dữ liệu cũ nếu có
            DB::table('lottery_results_index')->where('draw_date', $date)->delete();

            // Thêm dữ liệu mới
            DB::table('lottery_results_index')->insert($data);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lỗi khi thêm kết quả xổ số: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem có kết quả cho ngày cụ thể không
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @return bool Có hay không
     */
    public function hasResultsForDate($date)
    {
        return DB::table('lottery_results_index')
            ->where('draw_date', $date)
            ->exists();
    }

    /**
     * Lấy tất cả kết quả cho một ngày cụ thể
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @return array Mảng kết quả theo vị trí
     */
    public function getResultsForDate($date)
    {
        $results = DB::table('lottery_results_index')
            ->where('draw_date', $date)
            ->get();

        $positionValues = [];
        foreach ($results as $result) {
            $positionValues[$result->position] = $result->value;
        }

        return $positionValues;
    }

    /**
     * Lấy kết quả cho một khoảng ngày
     *
     * @param string $startDate Ngày bắt đầu (Y-m-d)
     * @param string $endDate Ngày kết thúc (Y-m-d)
     * @return array Mảng kết quả theo ngày và vị trí
     */
    public function getResultsForDateRange($startDate, $endDate)
    {
        $results = DB::table('lottery_results_index')
            ->whereBetween('draw_date', [$startDate, $endDate])
            ->orderBy('draw_date', 'desc')
            ->get();

        $groupedResults = [];
        foreach ($results as $result) {
            $date = $result->draw_date;
            if (!isset($groupedResults[$date])) {
                $groupedResults[$date] = [];
            }

            $groupedResults[$date][$result->position] = $result->value;
        }

        return $groupedResults;
    }

    /**
     * Lấy số lượng kết quả lưu trữ
     *
     * @return int Số lượng ngày có kết quả
     */
    public function getResultDaysCount()
    {
        return DB::table('lottery_results_index')
            ->select('draw_date')
            ->distinct()
            ->count();
    }

    /**
     * Xóa kết quả cũ hơn một ngày cụ thể
     *
     * @param string $date Ngày giới hạn (Y-m-d)
     * @return int Số bản ghi đã xóa
     */
    public function deleteResultsOlderThan($date)
    {
        return DB::table('lottery_results_index')
            ->where('draw_date', '<', $date)
            ->delete();
    }

    /**
     * Thống kê tần suất xuất hiện của giá trị theo vị trí
     *
     * @param string $position Vị trí (ví dụ: GDB-1-1)
     * @param int $limit Giới hạn số ngày gần nhất
     * @return array Thống kê tần suất
     */
    public function getValueFrequencyForPosition($position, $limit = 30)
    {
        $results = DB::table('lottery_results_index')
            ->where('position', $position)
            ->orderBy('draw_date', 'desc')
            ->limit($limit)
            ->pluck('value')
            ->toArray();

        $frequency = array_count_values($results);
        $stats = [];

        for ($i = 0; $i <= 9; $i++) {
            $stats[$i] = $frequency[$i] ?? 0;
        }

        return $stats;
    }
}
