<?php

namespace App\Http\Controllers;

use App\Models\LotteryCauLo;
use App\Services\LotteryCauLoHitService;
use Carbon\Carbon;
use App\Models\LotteryResult;
use App\Models\LotteryResultIndex;
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
        $cauLo = LotteryCauLo::with('formula')->findOrFail($id);
        $startDate = Carbon::parse(request('date', Carbon::today()->format('Y-m-d')));

        $timelineData = $this->cauLoHitService->getTimelineData(
            $cauLo,
            $startDate,
            30 // days before
        );

        return view('caulo.timeline', [
            'cauLo' => $cauLo,
            'meta' => $timelineData['meta'],
            'dateRange' => $timelineData['dateRange'],
            'hits' => $timelineData['hits'],
            'results' => $timelineData['results']
        ]);
    }
}