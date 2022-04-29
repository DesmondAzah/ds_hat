<?php 

namespace App\Repository;

use App\Models\PersonnelHatHistory;

class PersonnelHatHistoryRepository extends BaseRepository
{
    public function __construct(PersonnelHatHistory $personnelHat)
    {
        $this->model = $personnelHat;
    }
    

    public function personnelHatExits($personnel_id, $hat_id){
        return $this->model->where('personnel_id', $personnel_id)->where('hat_id', $hat_id)->first();
    }
}