<?php

namespace App\Imports;

use App\Models\Stop;
use App\Models\StopTime;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;

class StopTimesImport implements ToModel, WithHeadingRow, WithProgressBar, WithChunkReading
{
    use Importable;

    public function model(array $row)
    {
        if (!Stop::where('stop_id', $row['stop_id'])->exists()) {
            Stop::create([
                'stop_id' => $row['stop_id'],
                'stop_name' => 'Unknown',
                'stop_lat' => 0,
                'stop_lon' => 0,
            ]);
        }
        return new StopTime([
            'arrival_time'    => $this->formatTime($row['arrival_time']),
            'departure_time'  => $this->formatTime($row['departure_time']),
            'stop_sequence'   => $row['stop_sequence'],
            'pickup_type'     => $row['pickup_type'],
            'drop_off_type'   => $row['drop_off_type'],
            'trip_id'         => $row['trip_id'],
            'stop_id'         => $row['stop_id'],
        ]);
    }

    /**
     * Optionally format time fields (e.g., handle invalid/missing times)
     */
    private function formatTime($time)
    {
        // Basic fallback if empty
        return $time ?: '00:00:00';
    }

    /**
     * Use chunking for large datasets
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
