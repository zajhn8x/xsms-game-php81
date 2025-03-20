<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryCauLoMeta extends Model
{
    use HasFactory;

    protected $table = 'lottery_cau_lo_meta';

    protected $fillable = [
        'formula_name',
        'formula_note',
        'formula_structure',
        'combination_type'
    ];

    protected $casts = [
        'formula_structure' => 'json'
    ];

    /**
     * Lấy các cầu liên quan đến công thức này
     */
    public function caus()
    {
        return $this->hasMany(LotteryCauLo::class, 'formula_meta_id');
    }

    /**
     * Lấy các vị trí từ công thức
     */
    public function getPositionsAttribute()
    {
        if (!$this->formula_structure || !isset($this->formula_structure['positions'])) {
            return [];
        }

        return $this->formula_structure['positions'];
    }

    /**
     * Scope để lọc theo loại công thức
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('combination_type', $type);
    }
}
