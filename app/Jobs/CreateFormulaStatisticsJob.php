<?php

namespace App\Jobs;

use App\Services\FormulaStatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class CreateFormulaStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int    $formulaId;
    protected string $startDate;
    protected string $endDate;

    /**
     * Create a new job instance.
     *
     * @param int $formulaId
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(int $formulaId, string $startDate, string $endDate)
    {
        $this->formulaId = $formulaId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FormulaStatisticsService $formulaStatisticsService)
    {
        try{
            Log::info("Dispatched job for Formula ID: $this->formulaId, from {$this->startDate} to {$this->endDate}");
            $formulaStatisticsService->generateStatisticsFromHits($this->formulaId, $this->startDate, $this->endDate);
        } catch (Exception $e) {
            Log::error("⛔ Lỗi trong job: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
