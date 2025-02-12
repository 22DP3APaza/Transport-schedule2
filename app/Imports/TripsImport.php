<?php

namespace App\Imports;

use App\Models\Trip;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TripsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Trip([
            'trip_id' => $row['trip_id'],
            'trip_headsign' => $row['trip_headsign'],
            'direction_id' => $row['direction_id'],
            'block_id' => $row['block_id'],
            'wheelchair_accessible' => $row['wheelchair_accessible'],
            'route_id' => $row['route_id'],
            'service_id' => $row['service_id'],
            'shape_id' => $row['shape_id'],

        ]);
    }
}
