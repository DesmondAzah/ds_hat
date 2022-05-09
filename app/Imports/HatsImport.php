<?php

namespace App\Imports;

use App\Models\HatImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;


class HatsImport implements ToModel
{
    use Importable;
    public function collection(Collection $rows){
        foreach ($rows as $row){
            error_log(print_r($row, true));
        }
    }

    /**
    * @param array $row
    *
    * @return HatImport|null
    */
    public function model(array $row): ?HatImport
    {
        return new HatImport([
            'personnel_id' => $row[0],
            'parent_personnel_id' => $row[1],
            'personnel_name' => $row[2],
            'hat_name' => $row[3],
            'parent_hat_name' => $row[4],
            'hat_level' => $row[5],
            'hat_level_description' => $row[6],
            'hat_level_id' => $row[7],
            'hat_rank_id' => $row[8]
        ]);
    }
}