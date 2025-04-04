<?php

namespace App\Http\Controllers;

use App\Models\LotteryFormula;
use App\Services\FormulaHitService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use function GuzzleHttp\json_encode;

class CauLoController extends Controller
{
    protected $cauLoHitService;

    public function __construct(FormulaHitService $cauLoHitService)
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

    /**
     * Hiển thị timeline của cầu lô với chức năng tải thêm
     * @param int $id ID của cầu lô
     * @return \Illuminate\View\View
     */
    public function timeline($id)
    {
        $cauLo = LotteryFormula::with('formula')->findOrFail($id);
        $streak = request('streak', 2);
        $fromDate = Carbon::now()->subDays(30)->format('Y-m-d');
        
        $streakFormulas = $this->cauLoHitService->getStreakFormulas($fromDate, $streak);
        
        return view('caulo.timeline', [
            'cauLo' => $cauLo,
            'meta' => $cauLo->formula,
            'metaPosition' => $cauLo->formula->positions,
            'streakFormulas' => $streakFormulas,
            'currentStreak' => $streak
        ]);
    }
}
