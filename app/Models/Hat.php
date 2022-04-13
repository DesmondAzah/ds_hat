<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hat extends Model{
    protected $table = 'hats';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'hat_name',
        'hat_description',
        'hat_status',
        'created_by',
        'updated_by',
        'dt_updated'
    ];

    public function hat_level_rank () {
        return $this->hasMany(HatLevelRank::class, 'hat_id');
    }
}