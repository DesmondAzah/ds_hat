<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelHat extends Model {
    
        protected $table = 'personnel_hats';
        protected $primaryKey = 'id';
        public $timestamps = false;
        protected $fillable = [
            'personnel_id',
            'hat_lr_id',
            'type'
        ];
}