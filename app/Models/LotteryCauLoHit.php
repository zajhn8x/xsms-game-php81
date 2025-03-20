
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryCauLoHit extends Model
{
    public $timestamps = false;
    
    protected $table = 'lottery_cau_lo_hit';
    
    protected $fillable = [
        'cau_lo_id',
        'ngay',
        'so_trung'
    ];

    protected $casts = [
        'ngay' => 'date'
    ];

    public function cauLo()
    {
        return $this->belongsTo(LotteryCauLo::class, 'cau_lo_id');
    }
}
