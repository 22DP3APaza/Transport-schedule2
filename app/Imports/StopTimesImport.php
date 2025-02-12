<?php

namespace App\Imports;

use App\Models\StopTime;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithProgressBar;



class StopTimesImport implements ToModel, WithHeadingRow, WithProgressBar, WithChunkReading
{
    use Importable;

    public function model(array $row)
    {
        return new StopTime([
            'arrival_time' => $row['arrival_time'],
            'departure_time' => $row['departure_time'],
            'stop_sequence' => $row['stop_sequence'],
            'pickup_type' => $row['pickup_type'],
            'drop_off_type' => $row['drop_off_type'],
            'trip_id' => $row['trip_id'],
            'stop_id' => $row['stop_id'],
        ]);
    }
    private function formatTime($time)
    {
        return $time;
    }
    public function chunkSize(): int{
        return 1000;
    }

}
