<?php

namespace App\Jobs;

use App\Models\Campaign;
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
    public function handle(CampaignService $campaignService, HeatmapInsightService $heatmapService)
    {
        try {
            $campaign = Campaign::findOrFail($this->campaignId);

            // Kiểm tra trạng thái campaign
            if ($campaign->status !== 'running') {
                Log::warning("Campaign không ở trạng thái running", [
                    'campaign_id' => $this->campaignId,
                    'status' => $campaign->status
                ]);
                return;
            }

            // Lấy 3 cặp heatmap insight tốt nhất
            $insights = $heatmapService->getTopInsights(3, [
                'type' => 'long_run_stop',
                'day_stop' => 2 // Đang ở ngày thứ 2 sau khi dừng streak
            ]);

            if (empty($insights)) {
                Log::info("Không tìm thấy insight phù hợp", [
                    'campaign_id' => $this->campaignId
                ]);
                return;
            }

            // Tạo bet với các số từ insight
            $betNumbers = [];
            foreach ($insights as $insight) {
                $betNumbers[] = $insight->value;
            }

            // Thêm bet vào campaign
            $bet = $campaignService->addBet($campaign->id, [
                'bet_date' => now()->format('Y-m-d'),
                'bet_numbers' => $betNumbers,
                'bet_amount' => $campaign->bet_amount ?? 10000 // Số tiền mặc định
            ]);

            // Gửi thông báo
            CampaignNotificationJob::dispatch('bet_created', [
                'bet_id' => $bet->id
            ]);

            Log::info("Đã tạo bet tự động", [
                'campaign_id' => $this->campaignId,
                'bet_id' => $bet->id,
                'numbers' => $betNumbers
            ]);

        } catch (\Exception $e) {
            Log::error("Lỗi khi chạy campaign", [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage()
            ]);

            // Gửi thông báo lỗi
            CampaignNotificationJob::dispatch('error', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage()
            ]);
        }
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