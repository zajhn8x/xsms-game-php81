
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessLotteryFormula;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CheckLotteryFormulas extends Command
{
    protected $signature = 'lottery:check-formulas {--days=3}';
    protected $description = 'Check lottery formulas against results';

    public function handle()
    {
        $days = $this->option('days');
        $batchId = uniqid('formula_check_');
        $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $this->info("Checking formulas from {$startDate} to {$endDate}");
        
        ProcessLotteryFormula::dispatch($batchId, $startDate, $endDate);
        Cache::tags(['lottery_formulas'])->flush();
        
        $this->info('Formula check job dispatched successfully');
        return 0;
    }
}
