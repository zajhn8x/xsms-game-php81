
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LotteryCauLo;
use App\Models\LotteryCauLoMeta;
use App\Models\LotteryCauLoHit;
use App\Services\LotteryService;

class ProcessLotteryFormula implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $formula;
    protected $results;

    public function __construct(LotteryCauLo $formula, array $results)
    {
        $this->formula = $formula;
        $this->results = $results;
    }

    public function handle(LotteryService $lotteryService)
    {
        $formulaMeta = LotteryCauLoMeta::find($this->formula->formula_meta_id);
        if (!$formulaMeta) return;

        foreach ($this->results as $result) {
            $hit = $lotteryService->checkFormulaHit($formulaMeta, $result);
            if ($hit) {
                LotteryCauLoHit::create([
                    'cau_lo_id' => $this->formula->id,
                    'ngay' => $result->draw_date,
                    'so_trung' => $hit,
                ]);
            }
        }
    }
}
