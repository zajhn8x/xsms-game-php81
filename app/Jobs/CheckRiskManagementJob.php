<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RiskManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRiskManagementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    public $timeout = 60;
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(RiskManagementService $riskService): void
    {
        try {
            if ($this->userId) {
                // Check specific user
                $user = User::find($this->userId);
                if ($user) {
                    Log::info("Checking risk management for user {$this->userId}");
                    $riskService->checkUserRisk($user);
                }
            } else {
                // Check all active users
                Log::info('Starting risk management check for all active users');

                User::where('is_active', true)
                    ->whereHas('campaigns', function ($query) {
                        $query->where('status', 'active');
                    })
                    ->chunk(50, function ($users) use ($riskService) {
                        foreach ($users as $user) {
                            $riskService->checkUserRisk($user);
                        }
                    });

                Log::info('Completed risk management check for all users');
            }

        } catch (\Exception $e) {
            Log::error('Error in CheckRiskManagementJob: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckRiskManagementJob failed: ' . $exception->getMessage());
    }
}
