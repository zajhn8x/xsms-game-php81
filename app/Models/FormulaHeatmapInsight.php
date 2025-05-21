<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class FormulaHeatmapInsight extends Model
{
    protected $table = 'formula_heatmap_insights';

    protected $fillable = [
        'formula_id',
        'date',
        'type',
        'extra',
        'score'
    ];

    protected $casts = [
        'date' => 'date',
        'extra' => 'array',
        'score' => 'decimal:2'
    ];

    // Constants for type
    const TYPE_LONG_RUN = 'long_run';
    const TYPE_LONG_RUN_STOP = 'long_run_stop';
    const TYPE_REBOUND = 'rebound_after_long_run';

    // Constants for extra fields
    const EXTRA_STREAK_LENGTH = 'streak_length';
    const EXTRA_DAY_STOP = 'day_stop';
    const EXTRA_STEP_1 = 'step_1';
    const EXTRA_STEP_2 = 'step_2';
    const EXTRA_REBOUND_SUCCESS = 'rebound_success';
    const EXTRA_HIT_HISTORY = 'hit_history';
    const EXTRA_LAST_HIT_DATE = 'last_hit_date';

    /**
     * Get the formula that owns the insight
     */
    public function formula()
    {
        return $this->belongsTo(LotteryFormula::class, 'formula_id');
    }

    /**
     * Get hit record for this insight
     */
    public function getHit()
    {
        return FormulaHit::where('cau_lo_id', $this->formula_id)
            ->where('ngay', $this->date)
            ->first();
    }

    /**
     * Get suggested numbers from formula
     */
    public function getSuggestedNumbers(): array
    {
        if (!$this->formula) {
            return [];
        }

        $formula = $this->formula;
        $suggestedNumbers = [];

        // Lấy các vị trí từ công thức
        $positions = $formula->positions ?? [];
        
        // Lấy kết quả xổ số của ngày trước
        $previousDate = Carbon::parse($this->date)->subDay();
        $previousResult = LotteryResult::where('draw_date', $previousDate)->first();

        if ($previousResult && $previousResult->prizes) {
            foreach ($positions as $position) {
                if (isset($previousResult->prizes[$position])) {
                    $suggestedNumbers[] = $previousResult->prizes[$position];
                }
            }
        }

        return $suggestedNumbers;
    }

    /**
     * Get hit status text
     */
    public function getHitStatusText(): string
    {
        $hit = $this->getHit();
        if (!$hit) {
            return 'Chưa có kết quả';
        }

        $statusIcons = [
            0 => '🎯 Bình thường',
            1 => '🔁 Cùng chiều',
            2 => '💥 Hai nháy 1 số',
            3 => '🎊 Hai nháy 2 số',
            4 => '🔥 Nhiều hơn hai nháy',
        ];

        return $statusIcons[$hit->status] ?? '❔ Không rõ trạng thái';
    }

    /**
     * Get hit number
     */
    public function getHitNumber(): ?string
    {
        $hit = $this->getHit();
        return $hit ? $hit->so_trung : null;
    }

    /**
     * Get hit status
     */
    public function getHitStatus(): ?int
    {
        $hit = $this->getHit();
        return $hit ? $hit->status : null;
    }

    /**
     * Check if formula hit on this date
     */
    public function isHit(): bool
    {
        return $this->getHit() !== null;
    }

    /**
     * Scope a query to only include insights of a specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include insights for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope a query to only include insights with score greater than
     */
    public function scopeWithMinScore($query, $score)
    {
        return $query->where('score', '>=', $score);
    }

    /**
     * Get streak length from extra data
     */
    public function getStreakLength(): ?int
    {
        return $this->extra[self::EXTRA_STREAK_LENGTH] ?? null;
    }

    /**
     * Get days stopped from extra data
     */
    public function getDaysStopped(): ?int
    {
        return $this->extra[self::EXTRA_DAY_STOP] ?? null;
    }

    /**
     * Check if this is a step 1 insight
     */
    public function isStep1(): bool
    {
        return $this->extra[self::EXTRA_STEP_1] ?? false;
    }

    /**
     * Check if this is a step 2 insight
     */
    public function isStep2(): bool
    {
        return $this->extra[self::EXTRA_STEP_2] ?? false;
    }

    /**
     * Get rebound success status
     */
    public function getReboundSuccess(): ?bool
    {
        return $this->extra[self::EXTRA_REBOUND_SUCCESS] ?? null;
    }

    /**
     * Get hit history
     */
    public function getHitHistory(): array
    {
        return $this->extra[self::EXTRA_HIT_HISTORY] ?? [];
    }

    /**
     * Get last hit date
     */
    public function getLastHitDate(): ?string
    {
        return $this->extra[self::EXTRA_LAST_HIT_DATE] ?? null;
    }

    /**
     * Update extra data
     */
    public function updateExtra(array $data): void
    {
        $this->extra = array_merge($this->extra ?? [], $data);
        $this->save();
    }

    /**
     * Create a new insight
     */
    public static function createInsight(
        int $formulaId,
        string $date,
        string $type,
        array $extra,
        float $score = 0
    ): self {
        return self::updateOrCreate(
            ['formula_id' => $formulaId, 'date' => $date],
            [
                'formula_id' => $formulaId,
            'type' => $type,
            'extra' => $extra,
            'score' => $score
            ]
        );
    }

    /**
     * Get all active insights for a specific date
     */
    public static function getActiveInsights(string $date): \Illuminate\Database\Eloquent\Collection
    {
        return self::forDate($date)
            ->orderBy('score', 'desc')
            ->get();
    }

    /**
     * Get insights by type for a specific date
     */
    public static function getInsightsByType(string $date, string $type): \Illuminate\Database\Eloquent\Collection
    {
        return self::forDate($date)
            ->ofType($type)
            ->orderBy('score', 'desc')
            ->get();
    }
} 