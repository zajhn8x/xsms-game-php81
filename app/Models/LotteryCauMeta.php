
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryCauMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function cauLos()
    {
        return $this->hasMany(LotteryCauLo::class, 'formula_id');
    }
}
