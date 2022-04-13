<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HatRanks extends Model
{
    protected $table = 'hats_ranks';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'hat_rank',
        'hat_rank_description',
        'hat_rank_status',
        'created_by',
        'updated_by',
        'dt_updated'
    ];

    public function hat_level_rank () {
        return $this->hasMany(HatLevelRank::class, 'hat_rank_id');
    }
}