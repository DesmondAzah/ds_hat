<?php 

namespace App\Repository;

use App\Models\HatLevelRank;

class HatLevelRankRepository extends BaseRepository {
    
        public function __construct(HatLevelRank $hatLevelRank)
        {
            $this->model = $hatLevelRank;
        }
    
        public function findAllActive(){
            return $this->model->where('hat_level_rank_status', 1)->get();
        }

        public function hatLevelRankExits($hat_id, $level_id, $rank_id, $title){
            return $this->model->where('hat_id', $hat_id)->where('hat_level_id', $level_id)->where('hat_rank_id', $rank_id)->where('name', $title)->first();

        }

    }