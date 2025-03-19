
<?php

namespace App\Http\Controllers;

use App\Services\LotteryResultService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LotteryController extends Controller
{
    protected $lotteryService;

    public function __construct(LotteryResultService $lotteryService)
    {
        $this->lotteryService = $lotteryService;
    }

    public function index(Request $request)
    {
        $days = $request->get('days', 10);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && $endDate) {
            $results = $this->lotteryService->getResultsByDateRange($startDate, $endDate);
        } else {
            $results = $this->lotteryService->getLatestResults($days);
        }

        return view('lottery.index', compact('results', 'days'));
    }
}
