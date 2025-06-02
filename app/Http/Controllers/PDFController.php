<?php

namespace App\Http\Controllers;

use App\Models\Route as ModelRoute;
use App\Models\Stop;
use App\Models\Trip;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class PDFController extends Controller
{
    private function getTransportType($route_id)
    {
        if (str_contains($route_id, 'tram')) {
            return ['en' => 'Tram', 'lv' => 'Tramvajs'];
        } elseif (str_contains($route_id, 'trol')) {
            return ['en' => 'Trolleybus', 'lv' => 'Trolejbuss'];
        } elseif (str_contains($route_id, 'bus')) {
            return ['en' => 'Bus', 'lv' => 'Autobuss'];
        }
        return ['en' => 'Route', 'lv' => 'Maršruts'];
    }

    public function downloadStopTimes(Request $request, $route_id, $stop_id)
    {
        // Get the current language from the request
        $language = $request->query('lang', 'en');
        App::setLocale($language);

        // Get the database from the request
        $database = $request->query('database');

        if ($database) {
            $db = DB::connection($database);

            // Get route info
            $route = $db->table('routes')
                ->where('route_id', $route_id)
                ->first(['route_id', 'route_short_name', 'route_long_name', 'route_color']);

            if (!$route) {
                abort(404);
            }

            // Get stop info
            $stop = $db->table('stops')
                ->where('stop_id', $stop_id)
                ->first();

            if (!$stop) {
                abort(404);
            }

            // Get all trips for this route to find matching ones
            $trips = $db->table('trips')
                ->where('route_id', $route_id)
                ->pluck('trip_id');

            // Get the first trip to use as a reference for stop sequence
            $referenceTrip = $db->table('trips')
                ->where('route_id', $route_id)
                ->first();

            // Get all stops for this route in correct sequence
            $routeStops = $db->table('stops')
                ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
                ->where('stop_times.trip_id', $referenceTrip->trip_id)
                ->orderBy('stop_times.stop_sequence')
                ->get(['stops.stop_id', 'stops.stop_name']);

            // Get all stop times for this stop and route's trips
            $stopTimes = $db->table('stop_times')
                ->whereIn('trip_id', $trips)
                ->where('stop_id', $stop_id)
                ->orderBy('departure_time')
                ->get(['departure_time', 'trip_id']);
        } else {
        // Get route info
        $route = ModelRoute::where('route_id', $route_id)->firstOrFail([
            'route_id', 'route_short_name', 'route_long_name', 'route_color'
        ]);

        // Get transport type
        $transportType = $this->getTransportType($route_id);

        // Get stop info
        $stop = Stop::where('stop_id', $stop_id)->firstOrFail();

        // Get all trips for this route to find matching ones
        $trips = Trip::where('route_id', $route_id)->pluck('trip_id');

        // Get the first trip to use as a reference for stop sequence
        $referenceTrip = Trip::where('route_id', $route_id)->first();

        // Get all stops for this route in correct sequence
        $routeStops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $referenceTrip->trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.stop_id', 'stops.stop_name']);

        // Get all stop times for this stop and route's trips
        $stopTimes = DB::table('stop_times')
            ->whereIn('trip_id', $trips)
            ->where('stop_id', $stop_id)
            ->orderBy('departure_time')
            ->get(['departure_time', 'trip_id']);
        }

        // Get transport type
        $transportType = $this->getTransportType($route_id);

        // Split times into workdays and weekends based on service_id
        $workdayTimes = [];
        $weekendTimes = [];

        foreach ($stopTimes as $time) {
            if ($database) {
                $trip = $db->table('trips')
                    ->where('trip_id', $time->trip_id)
                    ->first();

                if (!$trip) continue;

                $service = $db->table('calendar')
                    ->where('service_id', $trip->service_id)
                    ->first();
            } else {
            $trip = Trip::where('trip_id', $time->trip_id)->first();
            if (!$trip) continue;

            $service = DB::table('calendar')
                ->where('service_id', $trip->service_id)
                ->first();
            }

            if ($service) {
                $timeStr = substr($time->departure_time, 0, 5);
                if ($service->monday && $service->tuesday && $service->wednesday && $service->thursday && $service->friday) {
                    $workdayTimes[] = $timeStr;
                }
                if ($service->saturday || $service->sunday) {
                    $weekendTimes[] = $timeStr;
                }
            }
        }

        // Sort the times
        sort($workdayTimes);
        sort($weekendTimes);

        // Generate PDF
        $pdf = PDF::loadView('pdfs.stop-times', [
            'route' => $route,
            'stop' => $stop,
            'workdayTimes' => $workdayTimes,
            'weekendTimes' => $weekendTimes,
            'language' => $language,
            'transportType' => $transportType,
            'routeStops' => $routeStops
        ]);

        // Generate filename with transport type
        $type = $language === 'lv' ? $transportType['lv'] : $transportType['en'];
        $filename = strtolower($type) . "_{$route->route_short_name}_" . strtolower($stop->stop_name) . "_schedule.pdf";

        // Return the PDF for viewing in browser
        return $pdf->stream($filename);
    }
}
