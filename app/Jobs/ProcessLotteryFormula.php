
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LotteryResult;
use App\Services\LotteryFormulaService;
use Illuminate\Support\Facades\Cache;

class ProcessLotteryFormula implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $startDate;
    protected $endDate;

    public function __construct($batchId, $startDate, $endDate)
    {
        $this->batchId = $batchId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle(LotteryFormulaService $formulaService)
    {
        $results = LotteryResult::whereBetween('draw_date', [$this->startDate, $this->endDate])
                               ->orderBy('draw_date')
                               ->get();

        foreach ($results as $result) {
            $formulaResults = $formulaService->calculateFormulaResults($result->draw_date);
            
            // Save results and update checkpoint
            Cache::put("formula_checkpoint_{$this->batchId}", $result->draw_date);
        }
    }
}
