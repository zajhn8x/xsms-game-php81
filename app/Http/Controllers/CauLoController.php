<?php

namespace App\Http\Controllers;

use App\Models\LotteryCauLoHit;
use App\Models\LotteryResult;
use App\Models\LotteryResultIndex;
use App\Services\LotteryCauLoHitService;
use App\Models\LotteryCauLo;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CauLoController extends Controller
{
    protected $cauLoHitService;

    public function __construct(LotteryCauLoHitService $cauLoHitService)
    {
        $this->cauLoHitService = $cauLoHitService;
    }

    public function find()
    {
        return view('caulo.find');
    }

    public function search()
    {
        $date = request('date', Carbon::today()->format('Y-m-d'));
        $hits = $this->cauLoHitService->findConsecutiveHits($date, 1);
        return response()->json($hits);
    }

    public function timeline($id)
    {
        $cauLo = LotteryCauLo::with('formula')->findOrFail($id); // Lấy công thức meta
        $startDate = Carbon::parse(request('date', Carbon::today()->format('Y-m-d')));
        $endDateBefore = $startDate->copy()->subDays(30);
        $endDateAfter = $startDate->copy()->addDays(30);

        // Tạo danh sách ngày
        $dateRange = collect();
        for ($date = $endDateBefore->copy(); $date <= $endDateAfter; $date->addDay()) {
            $dateRange->push($date->format('Y-m-d'));
        }

        // Lấy danh sách số trúng
        $hits = LotteryCauLoHit::where('cau_lo_id', $id)
            ->whereBetween('ngay', [$endDateBefore, $endDateAfter])
            ->orderBy('ngay', 'asc')
            ->get()
            ->map(fn($hit) => [
                'ngay' => $hit->ngay->format('Y-m-d') ,
                'so_trung' => $hit->so_trung
            ]);

        // Lấy kết quả lô từ bảng LotteryResult
        $results = LotteryResult::whereBetween('draw_date', [$endDateBefore, $endDateAfter])
            ->orderBy('draw_date', 'asc')
            ->get()
            ->mapWithKeys(fn($result) => [$result->draw_date->format('Y-m-d') => [
                "lo_array" => $result->lo_array,
                "prizes" => $result->prizes
            ]]);

        // 🔥 Lấy danh sách vị trí từ cầu lô meta
        $formula_structure = json_decode($cauLo->formula->formula_structure, true);
        $positions = \Arr::get($formula_structure,'positions', []) ;
        dump($positions);
        // 🔥 Lấy dữ liệu cầu lô từ LotteryResultIndex dựa vào vị trí
        $cauLoIndex = LotteryResultIndex::whereBetween('draw_date', [$endDateBefore, $endDateAfter])
            ->whereIn('position', $positions) // Lọc theo vị trí meta của cầu lô
            ->orderBy('draw_date', 'asc')
            ->get()
            ->groupBy('draw_date');

        // Chuyển `$hits` thành array key-value
        $hitsByDate = $hits->keyBy('ngay');
        // Lấy meta cầu lô
        $meta = [
            'formula_note' => $cauLo->formula->formula_note ?? 'Không có',
            'formula_name' => $cauLo->formula->formula_name ?? 'Không có',
            'hit_rate' => $cauLo->hit_rate . '%',
            'total_hits' => $cauLo->total_hits,
            'created_at' => $cauLo->created_at->format('d/m/Y'),
        ];
//        dump($results);
        return view('caulo.timeline', compact('cauLo', 'hitsByDate', 'dateRange', 'results', 'meta','cauLoIndex'));
    }
}
