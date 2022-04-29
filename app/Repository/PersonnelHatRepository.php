<?php 

namespace App\Repository;

use App\Models\PersonnelHat;

class PersonnelHatRepository extends BaseRepository
{
    public function __construct(PersonnelHat $personnelHat)
    {
        $this->model = $personnelHat;
    }
    
    public function findAllActive(){
        return $this->model->where('personnel_hat_status', 1)->get();
    }

    public function personnelHatExits($personnel_id, $hat_id){
        return $this->model->where('personnel_id', $personnel_id)->where('hat_lr_id', $hat_id)->first();
    }
}