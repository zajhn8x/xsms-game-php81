<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LotteryIndexResultsService
{
    /**
     * Lấy giá trị tại vị trí cụ thể cho một ngày xổ số
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @param string|array $position Vị trí (ví dụ: GDB-1-1, G7-4-2)
     * @return int|null Giá trị tại vị trí (0-9) hoặc null nếu không tìm thấy
     */
    public function getPositionValue($date, $position)
    {
        $result = DB::table('lottery_results_index')
            ->where('draw_date', $date)
            ->whereIn('position', $position)
            ->get();

        return $result ? $result->pluck('value')->toArray() : null;
    }
    /**
     * Lấy tất cả giá trị vị trí cho một ngày cụ thể
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @return array Map of positions and their values
     */
    public function getPositionsForDate($date)
    {
        $results = DB::table('lottery_results_index')
            ->where('draw_date', $date)
            ->get();

        $positionMap = [];
        foreach ($results as $result) {
            $positionMap[$result->position] = $result->value;
        }

        return $positionMap;
    }

    /**
     * Lấy danh sách các ngày có dữ liệu xổ số
     *
     * @param string|null $startDate Ngày bắt đầu (Y-m-d)
     * @param string|null $endDate Ngày kết thúc (Y-m-d)
     * @return array Danh sách các ngày xổ số
     */
    public function getDrawDates($startDate = null, $endDate = null)
    {
        $query = DB::table('lottery_results_index')
            ->select('draw_date')
            ->distinct();

        if ($startDate) {
            $query->where('draw_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('draw_date', '<=', $endDate);
        }

        return $query->orderBy('draw_date', 'desc')
            ->pluck('draw_date')
            ->toArray();
    }

    /**
     * Kiểm tra xem ngày xổ số có dữ liệu đầy đủ không
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @return bool True nếu có đầy đủ dữ liệu, false nếu không
     */
    public function isDateComplete($date)
    {
        $allPositions = $this->getAllConfigPositions();
        $datePositions = DB::table('lottery_results_index')
            ->where('draw_date', $date)
            ->pluck('position')
            ->toArray();

        $missingPositions = array_diff($allPositions, $datePositions);

        return count($missingPositions) === 0;
    }

    /**
     * Lấy tất cả các vị trí từ cấu hình
     *
     * @return array Danh sách các vị trí
     */
    public function getAllConfigPositions()
    {
        $positions = config('xsmb.positions');
        $allPositions = [];

        foreach ($positions as $groupPositions) {
            $allPositions = array_merge($allPositions, $groupPositions);
        }

        return $allPositions;
    }

    /**
     * Truy vấn lịch sử giá trị của một vị trí cụ thể
     *
     * @param string $position Vị trí (ví dụ: GDB-1-1)
     * @param int $limit Số lượng kết quả trả về
     * @param string|null $endDate Ngày kết thúc tìm kiếm
     * @return array Lịch sử giá trị
     */
    public function getPositionHistory($position, $limit = 30, $endDate = null)
    {
        $query = DB::table('lottery_results_index')
            ->where('position', $position)
            ->select('draw_date', 'value');

        if ($endDate) {
            $query->where('draw_date', '<=', $endDate);
        }

        return $query->orderBy('draw_date', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
