<?php

namespace App\Repository;

use App\Models\HatParentChild;

class HatPcrRepository extends BaseRepository {

    public function __construct(HatParentChild $hatPcr) {
        $this->model = $hatPcr;
    }

    public function findAllActive() {
        return $this->model->where('hat_pcr_status', 1)->get();
    }

    public function hatPcrExists($parentId,$childId){
        return $this->model->where('hat_lr_parent', $parentId)->where('hat_lr_child', $childId)->first();
    }

    public function getByColumns($columns, $values){
        return $this->model->whereIn($columns, $values)->get();
    }
    public function findUniqueParents(){
        return $this->model->select('hat_lr_parent')->distinct()->get();
    }

    public function hasParent ($childId){
        return $this->model->where('hat_lr_child', $childId)->first();
    }
    public function pcrExist($hatLrParent, $hatLrChild){
        return $this->model->where('hat_lr_parent', $hatLrParent)->where('hat_lr_child', $hatLrChild)->first();
    }
}