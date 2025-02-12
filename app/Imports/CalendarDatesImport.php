<?php

namespace App\Imports;

use App\Models\CalendarDate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CalendarDatesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new CalendarDate([
                'service_id' => $row['service_id'],
                'date' =>$row['date'],
                'exception_type' => $row['exception_type'],
            ]);

    }
}

