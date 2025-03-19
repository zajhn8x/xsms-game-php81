
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryCauLo extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'draw_date', 
        'lo_number',
        'formula_id',
        'occurrence'
    ];

    protected $casts = [
        'draw_date' => 'date'
    ];

    public function formula()
    {
        return $this->belongsTo(LotteryCauMeta::class, 'formula_id');
    }

    public function parent()
    {
        return $this->belongsTo(LotteryCauLo::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(LotteryCauLo::class, 'parent_id');
    }
}
