
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'draw_date',
        'prizes',
        'lo_array'
    ];

    protected $casts = [
        'draw_date' => 'date',
        'prizes' => 'array',
        'lo_array' => 'array'
    ];
}
