<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HatLevel extends Model
{
    protected $table = 'hats_level';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'hat_level',
        'hat_level_description',
        'hat_level_status',
        'created_by',
        'updated_by',
        'dt_updated'
    ];

    public function hat_level_rank () {
        return $this->hasMany(HatLevelRank::class, 'hat_level_id');
    }
}