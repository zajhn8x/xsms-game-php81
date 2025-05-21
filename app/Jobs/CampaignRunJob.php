<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Services\CampaignService;
use App\Services\HeatmapInsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(CampaignService $campaignService, HeatmapInsightService $insightService)
    {
        $campaign = Campaign::findOrFail($this->campaignId);

        if ($campaign->status !== 'running') {
            Log::info("Campaign {$this->campaignId} không ở trạng thái running");
            return;
        }

        $lastBet = CampaignBet::where('campaign_id', $this->campaignId)
            ->orderBy('bet_date', 'desc')
            ->first();

        $runDate = $lastBet ? $lastBet->bet_date->addDay() : $campaign->start_date;

        if ($runDate->gt($campaign->end_date)) {
            Log::info("Campaign {$this->campaignId} đã vượt quá ngày kết thúc");
            return;
        }

        $insights = $insightService->getTopInsights(
            $runDate->format('Y-m-d'),
            $campaign->strategy,
            $campaign->max_bets_per_day
        );

        if (empty($insights)) {
            Log::info("Không tìm thấy insight nào cho ngày {$runDate->format('Y-m-d')}");
            return;
        }

        foreach ($insights as $insight) {
            $campaignService->addBet($this->campaignId, [
                'bet_date' => $runDate->format('Y-m-d'),
                'bet_numbers' => $insight->formula->numbers,
                'bet_amount' => $campaign->bet_amount_per_number
            ]);
        }

        Log::info("Đã tạo bet cho campaign {$this->campaignId} ngày {$runDate->format('Y-m-d')}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job CampaignRunJob thất bại", [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage()
        ]);
    }
} 