<?php

namespace App\Jobs;

use App\Services\RealTimeCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRealTimeCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RealTimeCampaignService $realTimeCampaignService): void
    {
        try {
            Log::info('Starting real-time campaigns processing');

            $realTimeCampaignService->processActiveCampaigns();

            Log::info('Completed real-time campaigns processing');

        } catch (\Exception $e) {
            Log::error('Error in ProcessRealTimeCampaignsJob: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRealTimeCampaignsJob failed: ' . $exception->getMessage());
    }
}
