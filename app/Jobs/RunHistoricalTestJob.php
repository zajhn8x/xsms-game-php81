<?php

namespace App\Jobs;

use App\Services\HistoricalTestingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunHistoricalTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    protected $campaignId;

    /**
     * Create a new job instance.
     */
    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(HistoricalTestingService $historicalTestingService)
    {
        try {
            Log::info("Starting historical test for campaign {$this->campaignId}");

            $campaign = $historicalTestingService->runHistoricalTest($this->campaignId);

            Log::info("Completed historical test for campaign {$this->campaignId}. Final result: {$campaign->status}");

        } catch (\Exception $e) {
            Log::error("Failed to run historical test for campaign {$this->campaignId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Historical test job failed for campaign {$this->campaignId}: " . $exception->getMessage());

        // Update campaign status to failed
        \App\Models\HistoricalCampaign::find($this->campaignId)
            ?->update(['status' => 'failed']);
    }
}
