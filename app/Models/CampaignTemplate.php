<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Micro-task 2.1.2.2: Template creation API (4h)
 * Campaign Template Model for managing reusable campaign configurations
 */
class CampaignTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'category', 'user_id',
        'is_public', 'template_data', 'usage_count', 'rating'
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_public' => 'boolean',
        'rating' => 'decimal:2',
        'usage_count' => 'integer'
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(TemplateRating::class, 'template_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    public function scopeOwn($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeHighRated($query, $limit = 10)
    {
        return $query->where('rating', '>=', 4.0)->orderBy('rating', 'desc')->limit($limit);
    }

    /**
     * Micro-task 2.1.2.3: Template application logic (5h)
     * Get validated template data with defaults
     */
    public function getValidatedTemplateData(): array
    {
        $data = $this->template_data;

        return [
            'campaign_type' => $data['campaign_type'] ?? 'live',
            'initial_balance' => $data['initial_balance'] ?? 1000000,
            'daily_bet_limit' => $data['daily_bet_limit'] ?? null,
            'max_loss_per_day' => $data['max_loss_per_day'] ?? null,
            'total_loss_limit' => $data['total_loss_limit'] ?? null,
            'auto_stop_loss' => $data['auto_stop_loss'] ?? false,
            'auto_take_profit' => $data['auto_take_profit'] ?? false,
            'stop_loss_amount' => $data['stop_loss_amount'] ?? null,
            'take_profit_amount' => $data['take_profit_amount'] ?? null,
            'betting_strategy' => $data['betting_strategy'] ?? 'manual',
            'strategy_config' => $data['strategy_config'] ?? [],
            'bet_preferences' => $data['bet_preferences'] ?? [],
            'days' => $data['days'] ?? 30,
            'target_profit' => $data['target_profit'] ?? null,
            'notes' => $data['notes'] ?? null
        ];
    }

    /**
     * Increment usage count when template is used
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Update average rating based on all ratings
     */
    public function updateRating(): void
    {
        $avgRating = $this->ratings()->avg('rating');
        $this->update(['rating' => $avgRating ?? 0]);
    }

    /**
     * Check if user can access this template
     */
    public function canAccess($userId): bool
    {
        return $this->is_public ||
               $this->user_id === $userId ||
               $this->category === 'system';
    }

    /**
     * Get template preview data for display
     */
    public function getPreviewData(): array
    {
        $data = $this->template_data;

        return [
            'campaign_type' => $data['campaign_type'] ?? 'live',
            'initial_balance' => number_format($data['initial_balance'] ?? 0),
            'betting_strategy' => $data['betting_strategy'] ?? 'manual',
            'days' => $data['days'] ?? 30,
            'has_stop_loss' => $data['auto_stop_loss'] ?? false,
            'has_take_profit' => $data['auto_take_profit'] ?? false
        ];
    }

    /**
     * Create a campaign from this template
     */
    public function createCampaign($userId, array $overrides = []): Campaign
    {
        $templateData = $this->getValidatedTemplateData();

        $campaignData = array_merge($templateData, $overrides, [
            'user_id' => $userId,
            'template_id' => $this->id,
            'current_balance' => $templateData['initial_balance'],
            'status' => 'pending'
        ]);

        // Remove null values
        $campaignData = array_filter($campaignData, function ($value) {
            return $value !== null;
        });

        $campaign = Campaign::create($campaignData);

        // Increment template usage
        $this->incrementUsage();

        return $campaign;
    }
}
