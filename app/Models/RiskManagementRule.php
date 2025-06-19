<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskManagementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'rule_name', 'rule_type', 'conditions', 'actions',
        'is_active', 'is_global', 'threshold_amount', 'threshold_count',
        'time_window_hours', 'trigger_count', 'last_triggered_at', 'notes'
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'threshold_amount' => 'decimal:2',
        'last_triggered_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldTrigger($context): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if within time window
        if ($this->time_window_hours && $this->last_triggered_at) {
            $windowEnd = $this->last_triggered_at->addHours($this->time_window_hours);
            if (now() < $windowEnd) {
                return false;
            }
        }

        return $this->evaluateConditions($context);
    }

    public function trigger($context = []): array
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);

        $actions = $this->actions;
        $results = [];

        foreach ($actions as $action) {
            $results[] = $this->executeAction($action, $context);
        }

        return $results;
    }

    private function evaluateConditions($context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        return true;
    }

    private function evaluateCondition($condition, $context): bool
    {
        $type = $condition['type'] ?? null;
        $value = $condition['value'] ?? null;

        switch ($type) {
            case 'daily_loss_amount':
                return $context['daily_loss'] >= $value;
            case 'consecutive_losses':
                return $context['consecutive_losses'] >= $value;
            case 'win_streak_count':
                return $context['win_streak'] >= $value;
            case 'total_bet_amount':
                return $context['total_bet_amount'] >= $value;
            case 'balance_threshold':
                return $context['balance'] <= $value;
            default:
                return false;
        }
    }

    private function executeAction($action, $context): array
    {
        $type = $action['type'] ?? null;
        $params = $action['params'] ?? [];

        switch ($type) {
            case 'pause_campaigns':
                return $this->pauseUserCampaigns($params);
            case 'reduce_bet_amounts':
                return $this->reduceBetAmounts($params);
            case 'send_notification':
                return $this->sendNotification($params, $context);
            case 'limit_daily_betting':
                return $this->limitDailyBetting($params);
            default:
                return ['success' => false, 'message' => 'Unknown action type'];
        }
    }

    private function pauseUserCampaigns($params): array
    {
        $campaigns = $this->user->campaigns()->where('status', 'active')->get();
        $pausedCount = 0;

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'paused_by_risk_rule']);
            $pausedCount++;
        }

        return [
            'success' => true,
            'message' => "Paused {$pausedCount} campaigns",
            'action' => 'pause_campaigns'
        ];
    }

    private function reduceBetAmounts($params): array
    {
        $reductionPercent = $params['reduction_percent'] ?? 50;
        $campaigns = $this->user->campaigns()->where('status', 'active')->get();
        $updatedCount = 0;

        foreach ($campaigns as $campaign) {
            if (isset($campaign->strategy_config['base_bet_amount'])) {
                $newAmount = $campaign->strategy_config['base_bet_amount'] * (1 - $reductionPercent / 100);
                $config = $campaign->strategy_config;
                $config['base_bet_amount'] = $newAmount;
                $campaign->update(['strategy_config' => $config]);
                $updatedCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Reduced bet amounts by {$reductionPercent}% for {$updatedCount} campaigns",
            'action' => 'reduce_bet_amounts'
        ];
    }

    private function sendNotification($params, $context): array
    {
        $message = $params['message'] ?? 'Risk management rule triggered';

        // Here you would integrate with your notification system
        // For now, we'll just log it
        \Illuminate\Support\Facades\Log::warning("Risk Management Alert for User {$this->user_id}: {$message}", $context);

        return [
            'success' => true,
            'message' => 'Notification sent',
            'action' => 'send_notification'
        ];
    }

    private function limitDailyBetting($params): array
    {
        $limitAmount = $params['limit_amount'] ?? 0;

        // Update user's daily betting limit
        $this->user->update(['daily_bet_limit' => $limitAmount]);

        return [
            'success' => true,
            'message' => "Set daily betting limit to {$limitAmount} VND",
            'action' => 'limit_daily_betting'
        ];
    }
}
