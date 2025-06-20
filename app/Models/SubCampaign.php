<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Micro-task 2.1.4.2: Sub-campaign creation logic (4h)
 * Micro-task 2.1.4.3: Parent-child relationships (3h)
 * SubCampaign Model for managing campaign segments and splits
 */
class SubCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_campaign_id', 'name', 'description', 'type',
        'allocated_balance', 'current_balance', 'daily_bet_limit',
        'max_loss_per_day', 'stop_loss_amount', 'take_profit_amount',
        'betting_strategy', 'strategy_config', 'bet_preferences',
        'start_date', 'end_date', 'days',
        'status', 'auto_start', 'auto_stop', 'priority', 'weight',
        'metadata', 'notes'
    ];

    protected $casts = [
        'allocated_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'daily_bet_limit' => 'decimal:2',
        'max_loss_per_day' => 'decimal:2',
        'stop_loss_amount' => 'decimal:2',
        'take_profit_amount' => 'decimal:2',
        'strategy_config' => 'array',
        'bet_preferences' => 'array',
        'metadata' => 'array',
        'auto_start' => 'boolean',
        'auto_stop' => 'boolean',
        'weight' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_bet_amount' => 'decimal:2',
        'total_win_amount' => 'decimal:2',
        'total_loss_amount' => 'decimal:2'
    ];

    /**
     * Micro-task 2.1.4.3: Parent-child relationships (3h)
     * Relationships
     */
    public function parentCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'parent_campaign_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(CampaignBet::class, 'sub_campaign_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('parent_campaign_id', $campaignId);
    }

    /**
     * Accessor & Mutators
     */
    public function getProfitLossAttribute(): float
    {
        return $this->total_win_amount - $this->total_loss_amount;
    }

    public function getWinRateAttribute(): float
    {
        if ($this->total_bets === 0) return 0;
        return round(($this->winning_bets / $this->total_bets) * 100, 2);
    }

    public function getBalanceChangeAttribute(): float
    {
        return $this->current_balance - $this->allocated_balance;
    }

    public function getRoiAttribute(): float
    {
        if ($this->allocated_balance <= 0) return 0;
        return round(($this->profit_loss / $this->allocated_balance) * 100, 2);
    }

    public function getProgressPercentageAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) return 0;

        $totalDays = $this->start_date->diffInDays($this->end_date);
        if ($totalDays <= 0) return 100;

        $passedDays = $this->start_date->diffInDays(now());
        return min(100, max(0, round(($passedDays / $totalDays) * 100)));
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if sub-campaign can start
     */
    public function canStart(): bool
    {
        return $this->status === 'pending' &&
               $this->start_date <= now() &&
               $this->allocated_balance > 0 &&
               $this->parentCampaign->status === 'active';
    }

    /**
     * Check if sub-campaign should auto-stop
     */
    public function shouldAutoStop(): bool
    {
        if (!$this->auto_stop) return false;

        // Stop loss check
        if ($this->stop_loss_amount && $this->profit_loss <= -$this->stop_loss_amount) {
            return true;
        }

        // Take profit check
        if ($this->take_profit_amount && $this->profit_loss >= $this->take_profit_amount) {
            return true;
        }

        // End date check
        if ($this->end_date && now()->gt($this->end_date)) {
            return true;
        }

        // Balance depletion check
        if ($this->current_balance <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Start the sub-campaign
     */
    public function start(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update(['status' => 'active']);

        // Log activity
        $this->logActivity('started', 'Sub-campaign started');

        return true;
    }

    /**
     * Stop the sub-campaign
     */
    public function stop(string $reason = 'manual'): bool
    {
        if (!in_array($this->status, ['active', 'paused'])) {
            return false;
        }

        $status = $reason === 'completed' ? 'completed' : 'cancelled';
        $this->update(['status' => $status]);

        // Return unused balance to parent campaign
        if ($this->current_balance > 0) {
            $this->parentCampaign->increment('current_balance', $this->current_balance);
            $this->update(['current_balance' => 0]);
        }

        // Log activity
        $this->logActivity('stopped', "Sub-campaign stopped: {$reason}");

        return true;
    }

    /**
     * Pause the sub-campaign
     */
    public function pause(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update(['status' => 'paused']);
        $this->logActivity('paused', 'Sub-campaign paused');

        return true;
    }

    /**
     * Resume the sub-campaign
     */
    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        $this->update(['status' => 'active']);
        $this->logActivity('resumed', 'Sub-campaign resumed');

        return true;
    }

    /**
     * Allocate additional balance from parent campaign
     */
    public function allocateBalance(float $amount): bool
    {
        if ($amount <= 0) return false;

        $parentCampaign = $this->parentCampaign;
        if ($parentCampaign->current_balance < $amount) {
            return false;
        }

        // Transfer balance
        $parentCampaign->decrement('current_balance', $amount);
        $this->increment('allocated_balance', $amount);
        $this->increment('current_balance', $amount);

        $this->logActivity('balance_allocated', "Allocated {$amount} from parent campaign");

        return true;
    }

    /**
     * Process bet result and update statistics
     */
    public function processBetResult(array $betData): void
    {
        $amount = $betData['amount'];
        $isWin = $betData['is_win'];
        $winAmount = $betData['win_amount'] ?? 0;

        // Update bet statistics
        $this->increment('total_bets');
        $this->increment('total_bet_amount', $amount);

        if ($isWin) {
            $this->increment('winning_bets');
            $this->increment('total_win_amount', $winAmount);
            $this->increment('current_balance', $winAmount - $amount);
        } else {
            $this->increment('losing_bets');
            $this->increment('total_loss_amount', $amount);
            $this->decrement('current_balance', $amount);
        }

        // Check auto-stop conditions
        if ($this->shouldAutoStop()) {
            $this->stop('auto_stop');
        }
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        return [
            'balance' => [
                'allocated' => $this->allocated_balance,
                'current' => $this->current_balance,
                'change' => $this->balance_change,
                'change_percentage' => $this->allocated_balance > 0 ?
                    round(($this->balance_change / $this->allocated_balance) * 100, 2) : 0
            ],
            'betting' => [
                'total_bets' => $this->total_bets,
                'total_amount' => $this->total_bet_amount,
                'win_rate' => $this->win_rate,
                'profit_loss' => $this->profit_loss,
                'roi' => $this->roi
            ],
            'progress' => [
                'status' => $this->status,
                'progress_percentage' => $this->progress_percentage,
                'days_elapsed' => $this->start_date ? $this->start_date->diffInDays(now()) : 0,
                'days_remaining' => $this->end_date ? now()->diffInDays($this->end_date) : null
            ]
        ];
    }

    /**
     * Create sub-campaigns from parent campaign split
     */
    public static function createFromSplit(Campaign $parentCampaign, array $splitConfig): array
    {
        $subCampaigns = [];
        $totalWeight = array_sum(array_column($splitConfig, 'weight'));
        $availableBalance = $parentCampaign->current_balance;

        foreach ($splitConfig as $config) {
            $allocatedBalance = ($config['weight'] / $totalWeight) * $availableBalance;

            $subCampaign = self::create([
                'parent_campaign_id' => $parentCampaign->id,
                'name' => $config['name'],
                'description' => $config['description'] ?? null,
                'type' => $config['type'] ?? 'split',
                'allocated_balance' => $allocatedBalance,
                'current_balance' => $allocatedBalance,
                'betting_strategy' => $config['betting_strategy'] ?? $parentCampaign->betting_strategy,
                'strategy_config' => $config['strategy_config'] ?? $parentCampaign->strategy_config,
                'start_date' => $config['start_date'] ?? $parentCampaign->start_date,
                'end_date' => $config['end_date'] ?? $parentCampaign->end_date,
                'priority' => $config['priority'] ?? 1,
                'weight' => $config['weight'],
                'auto_start' => $config['auto_start'] ?? false,
                'auto_stop' => $config['auto_stop'] ?? true
            ]);

            $subCampaigns[] = $subCampaign;
        }

        // Deduct allocated balance from parent
        $parentCampaign->decrement('current_balance', $availableBalance);

        return $subCampaigns;
    }

    /**
     * Log activity for this sub-campaign
     */
    protected function logActivity(string $action, string $description): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => $this->parentCampaign->user_id,
                'activity_type' => 'sub_campaign',
                'activity_action' => $action,
                'description' => $description,
                'metadata' => [
                    'sub_campaign_id' => $this->id,
                    'parent_campaign_id' => $this->parent_campaign_id,
                    'sub_campaign_name' => $this->name
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }
}
