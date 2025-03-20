
<?php

namespace App\Http\Controllers;

use App\Services\LotteryCauLoHitService;
use App\Models\LotteryCauLo;
use Carbon\Carbon;

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
        $cauLo = LotteryCauLo::findOrFail($id);
        $startDate = request('date', Carbon::today()->format('Y-m-d'));
        $endDate = Carbon::parse($startDate)->subDays(30)->format('Y-m-d');
        
        $hits = LotteryCauLoHit::where('cau_lo_id', $id)
            ->whereBetween('ngay', [$endDate, $startDate])
            ->orderBy('ngay')
            ->get();
            
        return view('caulo.timeline', compact('cauLo', 'hits'));
    }
}
