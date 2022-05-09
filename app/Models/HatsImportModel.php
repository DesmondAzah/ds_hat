<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HatImportModel extends Model
{
    protected $table = 'hats_import';
    protected $fillable = [
        'personnel_id',
        'parent_personnel_id',
        'personnel_name',
        'hat_name',
        'parent_hat_name',
        'hat_level',
        'hat_level_description',
        'hat_level_id',
        'hat_rank_id'
        ];

}