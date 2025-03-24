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

    public function timeline($id)
    {
        $cauLo = LotteryFormula::with('formula')->findOrFail($id);
        $startDate = Carbon::parse(request('date', Carbon::today()->format('Y-m-d')));

        $timelineData = $this->cauLoHitService->getTimelineData(
            $cauLo,
            $startDate,
            30 // days before
        );
        dump($timelineData['results']);

        return view('caulo.timeline', [
            'cauLo' => $cauLo,
            'meta' => $cauLo->formula,
            'metaPosition' => json_encode($cauLo->formula->positions),
            'dateRange' => $timelineData['dateRange'],
            'hits' => $timelineData['hits'],
            'results' => $timelineData['results']
        ]);
    }
}
