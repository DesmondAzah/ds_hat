<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HatLevelRank extends Model {
    protected $table = 'hats_level_rank';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'hat_id',
        'hat_level_id',
        'hat_rank_id'
    ];

    public function hat () {
        return $this->belongsTo(Hat::class, 'hat_id');
    }

    public function hat_level () {
        return $this->belongsTo(HatLevel::class, 'hat_level_id');
    }

    public function hat_rank () {
        return $this->belongsTo(HatRanks::class, 'hat_rank_id');
    }

    public function hat_parent_child () {
        return $this->hasMany(HatParentChild::class, 'hat_lr_parent');
    }

    public function hat_parent_child_child () {
        return $this->hasMany(HatParentChild::class, 'hat_lr_child');
    }
}