<?php

namespace App\Imports;

use App\Models\Shape;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\Importable;

class ShapesImport implements ToModel, WithHeadingRow, WithProgressBar
{
    use Importable;


    public function model(array $row)
    {
        if (Shape::where('shape_id', $row['shape_id'])->exists()) {
            return null;
        }

        return new Shape([
            'shape_id' => $row['shape_id'],
            'shape_pt_lat' => $row['shape_pt_lat'],
            'shape_pt_lon' => $row['shape_pt_lon'],
            'shape_pt_sequence' => $row['shape_pt_sequence'],
            'shape_dist_traveled' => $row['shape_dist_traveled'] ?? 0,
        ]);
    }
}

