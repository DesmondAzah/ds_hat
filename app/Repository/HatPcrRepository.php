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

}