<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Template Rating Model for campaign template rating system
 */
class TemplateRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'user_id', 'rating', 'comment'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    /**
     * Relationships
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update template rating after this rating is saved
     */
    protected static function booted()
    {
        static::saved(function ($rating) {
            $rating->template->updateRating();
        });

        static::deleted(function ($rating) {
            $rating->template->updateRating();
        });
    }
}
