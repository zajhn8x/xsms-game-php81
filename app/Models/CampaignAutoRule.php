<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAutoRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'rule_type', 'conditions', 'actions',
        'priority', 'is_active', 'execution_count', 'last_executed_at',
        'min_bet_amount', 'max_bet_amount', 'cooldown_minutes'
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'min_bet_amount' => 'decimal:2',
        'max_bet_amount' => 'decimal:2',
        'last_executed_at' => 'datetime'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function canExecute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check cooldown
        if ($this->last_executed_at && $this->cooldown_minutes > 0) {
            $cooldownEnds = $this->last_executed_at->addMinutes($this->cooldown_minutes);
            if (now() < $cooldownEnds) {
                return false;
            }
        }

        return true;
    }

    public function markExecuted(): void
    {
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);
    }

    public function evaluateConditions($context = []): bool
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
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (!$field || !$operator || $value === null) {
            return false;
        }

        $actualValue = data_get($context, $field);

        switch ($operator) {
            case '>=':
                return $actualValue >= $value;
            case '<=':
                return $actualValue <= $value;
            case '>':
                return $actualValue > $value;
            case '<':
                return $actualValue < $value;
            case '==':
                return $actualValue == $value;
            case '!=':
                return $actualValue != $value;
            case 'in':
                return in_array($actualValue, (array) $value);
            case 'not_in':
                return !in_array($actualValue, (array) $value);
            default:
                return false;
        }
    }

    public function getExecutionActionsAttribute(): array
    {
        return $this->actions['on_execute'] ?? [];
    }

    public function getSuccessActionsAttribute(): array
    {
        return $this->actions['on_success'] ?? [];
    }

    public function getFailureActionsAttribute(): array
    {
        return $this->actions['on_failure'] ?? [];
    }
}
