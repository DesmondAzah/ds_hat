<?php

namespace App\Imports;

use App\Models\Hat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;


class ImportHats implements ToModel
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
    * @return Hat|null
    */
    public function model(array $row): ?Hat
    {
        return new Hat([
            'hat_name' => $row[0]
        ]);
    }
}