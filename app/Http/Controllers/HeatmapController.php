<?php

namespace App\Http\Controllers;

use App\Models\HeatmapDailyRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HeatmapController extends Controller
{
    /**
     * Hiển thị trang heatmap của các streak trong 20 ngày gần nhất
     */
    public function index()
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(19);

        // Lấy dữ liệu từ database
        $records = HeatmapDailyRecord::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Chuẩn bị dữ liệu cho view
        $processedData = [];
        foreach ($records as $record) {
            $date = $record->date->format('Y-m-d');
            $processedData[$date] = [
                'data' => array_map(function($cell) {
                    $extra = $cell['extra'] ?? [];
                    unset($extra['hit']); // Loại bỏ hit khỏi extra

                    return [
                        'id' => $cell['id'],
                        'streak' => $cell['streak'],
                        'hit' => $cell['hit'] ?? null,
                        'extra' => $extra,
                        'suggest' => $cell['suggest'] ?? null
                    ];
                }, $record->data)
            ];
        }

        return view('heatmap.index', [
            'heatmapData' => $processedData,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
