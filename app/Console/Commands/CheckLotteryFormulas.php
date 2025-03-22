
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas {--batch-size=100} {--resume}';
    protected $description = 'Check lottery formulas with support for resuming';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $shouldResume = $this->option('resume');
        $batchId = uniqid('formula_batch_');

        // Get date range
        $startDate = LotteryResult::min('draw_date');
        $endDate = LotteryResult::max('draw_date');

        if ($shouldResume) {
            $lastCheckpoint = Cache::get('formula_last_checkpoint');
            if ($lastCheckpoint) {
                $startDate = Carbon::parse($lastCheckpoint)->addDay();
                $this->info("Resuming from: " . $startDate);
            }
        }

        // Split date range into batches
        $currentDate = Carbon::parse($startDate);
        $endDateTime = Carbon::parse($endDate);

        while ($currentDate <= $endDateTime) {
            $batchEndDate = (clone $currentDate)->addDays($batchSize);
            if ($batchEndDate > $endDateTime) {
                $batchEndDate = $endDateTime;
            }

            ProcessLotteryFormula::dispatch(
                $batchId,
                $currentDate->format('Y-m-d'),
                $batchEndDate->format('Y-m-d')
            );

            $this->info("Queued batch: {$currentDate->format('Y-m-d')} to {$batchEndDate->format('Y-m-d')}");
            $currentDate = $batchEndDate->addDay();
        }

        $this->info('All batches have been queued.');
    }
}
