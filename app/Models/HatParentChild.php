<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HatParentChild extends Model {
    protected $table = 'hats_parent_child';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'hat_lr_parent',
        'hat_lr_child'
        ];

    public function hat_level_rank_parent () {
        return $this->belongsTo(PersonnelHat::class, 'hat_lr_parent');
    }   

    public function hat_level_rank_child () {
        return $this->belongsTo(HatLevelRank::class, 'hat_lr_child');
    }
}