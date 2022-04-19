<?php 

namespace App\Repository;

use App\Models\HatLevel;

class HatLevelRepository extends BaseRepository{
    public function __construct(HatLevel $hatLevel)
    {
        $this->model = $hatLevel;
    }
    
    public function findAllActive(){
        return $this->model->where('hat_level_status', 1)->get();
    }
}