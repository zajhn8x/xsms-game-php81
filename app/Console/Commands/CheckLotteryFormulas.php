<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LotteryService;
use App\Models\LotteryCauLo;
use App\Jobs\ProcessLotteryFormula;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas {--chunk=100} {--queue=default}';
    protected $description = 'Check lottery formulas against results using parallel processing';

    protected $lotteryService;

    public function __construct(LotteryService $lotteryService)
    {
        parent::__construct();
        $this->lotteryService = $lotteryService;
    }

    public function handle()
    {
        $this->info('Starting parallel formula checks...');

        $chunk = $this->option('chunk');
        $queue = $this->option('queue');

        $today = Carbon::now()->format('Y-m-d');
        $results = $this->lotteryService->getResultsByDateRange(
            Carbon::now()->subDays(30),
            $today
        );

        LotteryCauLo::where('is_active', true)
            ->chunk($chunk, function($formulas) use ($results, $queue) {
                $jobs = $formulas->map(function($formula) use ($results) {
                    return new ProcessLotteryFormula($formula, $results);
                });

                Bus::batch($jobs)
                    ->onQueue($queue)
                    ->allowFailures()
                    ->dispatch();

                $this->info("Dispatched batch of {$formulas->count()} formulas");
            });

        $this->info('All formula check jobs have been queued');
    }
}