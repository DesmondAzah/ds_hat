<?php 

namespace App\Repository;

use App\Models\Hat;

class HatRepository extends BaseRepository
{
    public function __construct(Hat $hat)
    {
        $this->model = $hat;
    }

    /** 
     * Find all active hats
     */
    public function findAllActive(){
        return $this->model->where('hat_status', 1)->get();
    }
}