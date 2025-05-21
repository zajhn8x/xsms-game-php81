<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    /**
     * Create a new job instance.
     */
    public function __construct($date = null)
    {
        $this->date = $date ?? now()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService)
    {
        try {
            // Lấy danh sách bet chưa có kết quả
            $bets = CampaignBet::where('bet_date', $this->date)
                ->whereNull('result')
                ->get();

            foreach ($bets as $bet) {
                $campaign = $bet->campaign;

                // Kiểm tra kết quả xổ số
                $result = $campaignService->checkBetResult($bet);

                // Cập nhật kết quả bet
                $bet->update([
                    'result' => $result['hit_count'],
                    'profit' => $result['profit']
                ]);

                // Cập nhật thống kê campaign
                $campaignService->updateCampaignStats($campaign->id);

                Log::info("Đã cập nhật kết quả bet", [
                    'bet_id' => $bet->id,
                    'campaign_id' => $campaign->id,
                    'result' => $result
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Lỗi khi cập nhật kết quả", [
                'date' => $this->date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job CampaignResultJob thất bại", [
            'date' => $this->date,
            'error' => $exception->getMessage()
        ]);
    }
} 