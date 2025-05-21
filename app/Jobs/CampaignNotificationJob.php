<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Notifications\CampaignBetCreated;
use App\Notifications\CampaignBetResult;
use App\Notifications\CampaignError;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CampaignNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            switch ($this->type) {
                case 'bet_created':
                    $this->handleBetCreated();
                    break;
                case 'bet_result':
                    $this->handleBetResult();
                    break;
                case 'error':
                    $this->handleError();
                    break;
                default:
                    Log::warning("Loại thông báo không hợp lệ", [
                        'type' => $this->type
                    ]);
            }
        } catch (\Exception $e) {
            Log::error("Lỗi khi gửi thông báo", [
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xử lý thông báo tạo bet mới
     */
    protected function handleBetCreated()
    {
        $bet = CampaignBet::find($this->data['bet_id']);
        if (!$bet) return;

        $campaign = $bet->campaign;
        $user = $campaign->user;

        Notification::send($user, new CampaignBetCreated($bet));
    }

    /**
     * Xử lý thông báo kết quả bet
     */
    protected function handleBetResult()
    {
        $bet = CampaignBet::find($this->data['bet_id']);
        if (!$bet) return;

        $campaign = $bet->campaign;
        $user = $campaign->user;

        Notification::send($user, new CampaignBetResult($bet));
    }

    /**
     * Xử lý thông báo lỗi
     */
    protected function handleError()
    {
        $campaign = Campaign::find($this->data['campaign_id']);
        if (!$campaign) return;

        $user = $campaign->user;

        Notification::send($user, new CampaignError(
            $campaign,
            $this->data['error']
        ));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job CampaignNotificationJob thất bại", [
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);
    }
} 