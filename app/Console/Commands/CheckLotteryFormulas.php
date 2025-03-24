<?php
namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas 
                           {--days=3 : Number of days to check}
                           {--start-date= : Optional start date in Y-m-d format}';

    protected $description = 'Check lottery formulas against results';

    public function handle()
{
    $days = $this->option('days');
    $userStartDate = $this->option('start-date');
    $batchId = uniqid('formula_check_');

    if ($userStartDate) {
        // Use user provided start date
        try {
            $startDate = Carbon::parse($userStartDate)->format('Y-m-d');
            $endDate = Carbon::parse($userStartDate)->addDays($days - 1)->format('Y-m-d');
        } catch (Exception $e) {
            $this->error('Invalid date format. Please use Y-m-d format (e.g., 2025-03-22)');
            return 1;
        }
    } else {
        // Use default calculation based on today minus days
        $startDate = Carbon::now()->subDays($days - 1)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
    }

    $this->info("Checking formulas from {$startDate} to {$endDate}");

    ProcessLotteryFormula::dispatch($batchId, $startDate, $endDate);
//        Cache::tags(['lottery_formulas'])->flush();

    $this->info('Formula check job dispatched successfully');
    return 0;
}
}
