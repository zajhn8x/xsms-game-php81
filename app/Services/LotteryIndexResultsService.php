<?php

namespace App\Services;

use App\Models\LotteryResultIndex;
use Illuminate\Support\Facades\DB;

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
        $result = LotteryResultIndex::where('draw_date', $date)
            ->whereIn('position', (array) $position)
            ->pluck('value')
            ->toArray();

        return $result ?: null;
    }

    /**
     * Lấy tất cả giá trị vị trí cho một ngày cụ thể
     *
     * @param string $date Ngày xổ số (Y-m-d)
     * @return array Map of positions and their values
     */
    public function getPositionsForDate($date)
    {
        return LotteryResultIndex::where('draw_date', $date)
            ->pluck('value', 'position')
            ->toArray();
    }

    /**
     * Lấy danh sách các ngày có dữ liệu xổ số, có lọc theo vị trí cụ thể.
     *
     * @param array $positions Danh sách vị trí cần lọc (ví dụ: ['G2-2-1', 'G5-3-2'])
     * @param string|null $startDate Ngày bắt đầu (Y-m-d)
     * @param string|null $endDate Ngày kết thúc (Y-m-d)
     * @return array Danh sách các ngày xổ số, group theo ngày.
     */
    public function getDrawDates(array $positions, $startDate = null, $endDate = null)
    {
        // Lấy danh sách vị trí hợp lệ từ config
        $validPositions = collect(config('xsmb.positions'))->flatten()->toArray();

        // Lọc bỏ những position không hợp lệ
        $positions = array_intersect($positions, $validPositions);

        if (empty($positions)) {
            return []; // Nếu không có position hợp lệ, trả về mảng rỗng
        }

        // Truy vấn dữ liệu
        $query = LotteryResultIndex::select('draw_date','value' ,'position')
            ->whereIn('position', $positions);

        if ($startDate) {
            $query->where('draw_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('draw_date', '<=', $endDate);
        }

        // Lấy kết quả và nhóm theo ngày
        return $query->orderBy('draw_date', 'desc')
            ->get()
            ->groupBy('draw_date')
            ->map(fn ($items) => $items->pluck('value')->toArray())
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
        $datePositions = LotteryResultIndex::where('draw_date', $date)
            ->pluck('position')
            ->toArray();

        return empty(array_diff($allPositions, $datePositions));
    }

    /**
     * Lấy tất cả các vị trí từ cấu hình
     *
     * @return array Danh sách các vị trí
     */
    public function getAllConfigPositions()
    {
        $positions = config('xsmb.positions');
        return array_merge(...array_values($positions));
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
        $query = LotteryResultIndex::where('position', $position)
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
