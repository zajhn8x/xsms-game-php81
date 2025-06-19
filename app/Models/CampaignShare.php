<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'shared_by_user_id', 'share_platform',
        'share_url', 'click_count', 'analytics'
    ];

    protected $casts = [
        'analytics' => 'array'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }

    public function addAnalytics($data): void
    {
        $analytics = $this->analytics ?? [];
        $analytics[] = array_merge($data, ['timestamp' => now()->toISOString()]);
        $this->update(['analytics' => $analytics]);
    }
}
