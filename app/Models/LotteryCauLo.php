<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryCauLo extends Model
{
    use HasFactory;

    protected $table = 'lottery_cau_lo';

    protected $fillable = [
        'combination_type',
        'formula_meta_id',
        'is_processed',
        'processed_days',
        'last_processed_date',
        'processing_status',
        'result_data'
    ];

    protected $casts = [
        'is_processed' => 'boolean',
        'last_processed_date' => 'date',
        'result_data' => 'json'
    ];

    /**
     * Lấy công thức meta liên quan
     */
    public function formula()
    {
        return $this->belongsTo(LotteryCauMeta::class, 'formula_meta_id');
    }

    /**
     * Lấy tỷ lệ trúng
     */
    public function getHitRateAttribute()
    {
        $resultData = $this->result_data;
        if (!$resultData || !isset($resultData['stats']['hit_rate'])) {
            return 0;
        }

        return $resultData['stats']['hit_rate'];
    }

    /**
     * Lấy tổng số lần trúng
     */
    public function getTotalHitsAttribute()
    {
        $resultData = $this->result_data;
        if (!$resultData || !isset($resultData['stats']['total_hits'])) {
            return 0;
        }

        return $resultData['stats']['total_hits'];
    }

    /**
     * Scope để lấy các cầu đang xử lý
     */
    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'in_progress');
    }

    /**
     * Scope để lấy các cầu chưa xử lý
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    /**
     * Scope để lấy các cầu đã hoàn thành
     */
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope để lọc theo loại cầu
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('combination_type', $type);
    }
}
