<?php

namespace App\Imports;

use App\Models\Stop;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StopsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Stop([
            'stop_id' => $row['stop_id'],
            'stop_name' => $row['stop_name'],
            'stop_desc' => $row['stop_desc'] ?? null,
            'stop_code' => $row['stop_code'],
            'stop_lat' => $row['stop_lat'],
            'stop_lon' => $row['stop_lon'],
            'stop_url' => $row['stop_url'],
            'location_type' => $row['location_type'],
            'parent_station' => $row['parent_station'],
        ]);

    }
}
