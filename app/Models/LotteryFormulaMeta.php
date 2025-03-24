<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use function GuzzleHttp\json_decode;
use function PHPUnit\Framework\isJson;

class LotteryFormulaMeta extends Model
{
    use HasFactory;

    protected $table = 'lottery_formula_meta';

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
        return $this->hasMany(LotteryFormula::class, 'formula_meta_id');
    }

    /**
     * Lấy các vị trí từ công thức
     */
    public function getPositionsAttribute()
    {

        if(!empty($this->formula_structure) && isJson($this->formula_structure)){
            // Đảm bảo giá trị trả về là một mảng chuỗi công thức
            return array_map('strval', Arr::get(json_decode($this->formula_structure,true),'positions',[]) );
        }
        // Kiểm tra nếu formula_structure không tồn tại hoặc không chứa 'positions'
        else if (empty($this->formula_structure) || !isset($this->formula_structure['positions'])) {
            return ['a'];
        }

        // Đảm bảo giá trị trả về là một mảng chuỗi công thức
        return array_map('strval', (array) $this->formula_structure['positions']);
    }

    /**
     * Scope để lọc theo loại công thức
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('combination_type', $type);
    }
}
