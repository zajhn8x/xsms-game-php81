<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\Cache;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas';
    protected $description = 'Check lottery formulas against results';

    public function handle()
    {
        $results = LotteryResult::orderBy('draw_date', 'desc')->take(3)->get();
        ProcessLotteryFormula::dispatch($results);
        Cache::tags(['lottery_formulas'])->flush();
        $this->info('Formula check job dispatched successfully');
    }
}