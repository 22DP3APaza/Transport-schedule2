<?php

namespace App\Imports;

use App\Models\Calendar;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CalendarImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Calendar([
            'service_id' => $row['service_id'],
            'monday' => $row['monday'],
            'tuesday' => $row['tuesday'],
            'wednesday' => $row['wednesday'],
            'thursday' => $row['thursday'],
            'friday' => $row['friday'],
            'saturday' => $row['saturday'],
            'sunday' => $row['sunday'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
        ]);
    }
}
