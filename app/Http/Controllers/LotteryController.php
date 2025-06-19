<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LotteryService;

class LotteryController extends Controller
{
    protected $lotteryService;

    public function __construct(LotteryService $lotteryService)
    {
        $this->lotteryService = $lotteryService;
    }

    public function index()
    {
        $results = $this->lotteryService->getResults(5);
        return view('lottery.index', compact('results'));
    }
}