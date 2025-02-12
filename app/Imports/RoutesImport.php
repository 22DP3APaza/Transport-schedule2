<?php

namespace App\Imports;

use App\Models\Route;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RoutesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Route([
            'route_id' => $row['route_id'],
            'route_short_name' => $row['route_short_name'],
            'route_long_name' => $row['route_long_name'],
            'route_desc' => $row['route_desc'],
            'route_type' => $row['route_type'],
            'route_url' => $row['route_url'],
            'route_color' => $row['route_color'],
            'route_text_color' => $row['route_text_color'],
            'route_sort_order' => $row['route_sort_order'],
        ]);
    }
}
