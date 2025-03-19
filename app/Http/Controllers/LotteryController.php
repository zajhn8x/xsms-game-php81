<?php

namespace App\Http\Controllers;

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
        $results = $this->lotteryService->getResults();
        return view('lottery.index', compact('results'));
    }
}