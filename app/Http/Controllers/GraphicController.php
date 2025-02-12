<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ShapesImport;
use App\Imports\CalendarImport;
use App\Imports\StopsImport;
use App\Imports\RoutesImport;
use App\Imports\CalendarDatesImport;
use App\Imports\TripsImport;
use App\Imports\StopTimesImport;

class GraphicController extends Controller
{
    public function importExcelData(Request $request, $type)
    {
        $request->validate([
            'import_file' => ['required', 'file'],
        ]);

        // Map import types to corresponding classes
        $importClasses = [
            'shapes' => ShapesImport::class,
            'calendar' => CalendarImport::class,
            'stops' => StopsImport::class,
            'routes' => RoutesImport::class,
            'calendar_dates' => CalendarDatesImport::class,
            'trips' => TripsImport::class,
            'stop_times' => StopTimesImport::class,
        ];

        if (!isset($importClasses[$type])) {
            return response()->json(['message' => 'Invalid import type.'], 400);
        }

        try {
            Excel::import(new $importClasses[$type], $request->file('import_file'));
            return response()->json(['statuss' => 'Imported Successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
