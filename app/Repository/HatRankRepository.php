<?php 

namespace App\Repository;

use App\Models\HatRanks;

class HatRankRepository extends BaseRepository
{
    public function __construct(HatRanks $hatRank)
    {
        $this->model = $hatRank;
    }
    
    public function findAllActive(){
        return $this->model->where('hat_rank_status', 1)->get();
    }
}