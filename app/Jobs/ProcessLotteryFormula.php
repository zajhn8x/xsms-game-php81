<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LotteryResult;
use App\Models\LotteryFormula;
use App\Services\LotteryFormulaService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProcessLotteryFormula implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $startDate;
    protected $endDate;

    public function __construct($batchId, $startDate = null, $endDate = null)
    {
        $this->batchId = $batchId;
        $this->startDate = $startDate ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $this->endDate = $endDate ?? Carbon::now()->format('Y-m-d');
    }

    public function handle(LotteryFormulaService $formulaService)
    {
        $results = LotteryResult::whereBetween('draw_date', [$this->startDate, $this->endDate])
                               ->orderBy('draw_date')
                               ->get();

        $cauLos = LotteryFormula::where('is_processed', false)->get();

        foreach ($cauLos as $cauLo) {
            foreach ($results as $result) {
                $formulaService->calculateResults($cauLo->id, $result->draw_date);
            }

            $cauLo->is_processed = true;
            $cauLo->last_processed_date = Carbon::now();
            $cauLo->save();
        }

        Cache::put("formula_checkpoint_{$this->batchId}", [
            'processed_at' => Carbon::now(),
            'cau_count' => $cauLos->count(),
            'result_count' => $results->count()
        ]);
    }
}
