<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    public function index()
    {
        // Get unique routes based on first and last stop
        $routes = DB::connection('mysql_trains')
            ->table('trips')
            ->join('stop_times as first_stop', function ($join) {
                $join->on('trips.trip_id', '=', 'first_stop.trip_id')
                    ->where('first_stop.stop_sequence', '=', DB::raw('(SELECT MIN(stop_sequence) FROM stop_times WHERE trip_id = trips.trip_id)'));
            })
            ->join('stop_times as last_stop', function ($join) {
                $join->on('trips.trip_id', '=', 'last_stop.trip_id')
                    ->where('last_stop.stop_sequence', '=', DB::raw('(SELECT MAX(stop_sequence) FROM stop_times WHERE trip_id = trips.trip_id)'));
            })
            ->join('stops as start_station', 'first_stop.stop_id', '=', 'start_station.stop_id')
            ->join('stops as end_station', 'last_stop.stop_id', '=', 'end_station.stop_id')
            ->join('routes', 'trips.route_id', '=', 'routes.route_id')
            ->select([
                'routes.route_id',
                'start_station.stop_name as from_station',
                'end_station.stop_name as to_station',
                'trips.trip_id'
            ])
            ->distinct()
            ->get()
            ->unique(function ($route) {
                return $route->from_station . '-' . $route->to_station;
            })
            ->values();

        return Inertia::render('train', [
            'routes' => $routes
        ]);
    }

    public function getStops()
    {
        $stops = DB::connection('mysql_trains')
            ->table('stops')
            ->select([
                'stop_id',
                'stop_name',
                'stop_lat',
                'stop_lon'
            ])
            ->get();

        return response()->json($stops);
    }

    public function searchRoute($fromStop, $toStop)
    {
        // First get the trips that serve these stops
        $trips = DB::connection('mysql_trains')
            ->table('stop_times as st1')
            ->join('stop_times as st2', 'st1.trip_id', '=', 'st2.trip_id')
            ->join('trips', 'st1.trip_id', '=', 'trips.trip_id')
            ->join('routes', 'trips.route_id', '=', 'routes.route_id')
            ->join('stops as from_stop', 'st1.stop_id', '=', 'from_stop.stop_id')
            ->join('stops as to_stop', 'st2.stop_id', '=', 'to_stop.stop_id')
            ->where(function($query) use ($fromStop, $toStop) {
                // Direct route search
                $query->where(function($q) use ($fromStop, $toStop) {
                    $q->where('st1.stop_id', $fromStop)
                      ->where('st2.stop_id', $toStop)
                      ->where('st1.stop_sequence', '<', 'st2.stop_sequence');
                });

                // Also search by route name pattern
                $query->orWhereExists(function($subquery) use ($fromStop, $toStop) {
                    $subquery->select(DB::raw(1))
                            ->from('routes as r')
                            ->whereColumn('r.route_id', 'routes.route_id')
                            ->where(function($q) use ($fromStop, $toStop) {
                                $q->whereRaw("CONCAT(from_stop.stop_name, ' - ', to_stop.stop_name) LIKE routes.route_long_name")
                                  ->orWhereRaw("CONCAT(to_stop.stop_name, ' - ', from_stop.stop_name) LIKE routes.route_long_name");
                            });
                });
            })
            ->select([
                'routes.route_id',
                'routes.route_short_name',
                'routes.route_long_name',
                'st1.departure_time as departure',
                'st2.arrival_time as arrival',
                'trips.trip_id',
                'from_stop.stop_name as from_station',
                'to_stop.stop_name as to_station'
            ])
            ->orderBy('st1.departure_time')
            ->get();

        return response()->json($trips);
    }

    public function showDetails($routeId, $tripId = null)
    {
        // Get route info
        $route = DB::connection('mysql_trains')
            ->table('routes')
            ->where('route_id', $routeId)
            ->first();

        // Get all trips for this route
        $trips = DB::connection('mysql_trains')
            ->table('trips')
            ->where('route_id', $routeId)
            ->get();

        $enhancedTrips = $trips->map(function ($trip) {
            // Get all stops for this trip with their sequence
            $tripStops = DB::connection('mysql_trains')
                ->table('stop_times')
                ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
                ->where('stop_times.trip_id', $trip->trip_id)
                ->orderBy('stop_times.stop_sequence')
                ->get(['stops.stop_name', 'stop_times.stop_sequence']);

            if ($tripStops->isEmpty()) {
                return null;
            }

            // Get first and last stops
            $firstStop = $tripStops->first();
            $lastStop = $tripStops->last();

            // Store the stop sequence for comparison
            $trip->stop_sequence = $tripStops->pluck('stop_sequence')->toArray();
            $trip->full_name = $firstStop->stop_name . ' → ' . $lastStop->stop_name;

            return $trip;
        })->filter();

        // If selected trip, use its stop sequence as reference
        $referenceSequence = null;
        if ($tripId) {
            $selectedTrip = $enhancedTrips->firstWhere('trip_id', $tripId);
            if ($selectedTrip) {
                $referenceSequence = $selectedTrip->stop_sequence;
            }
        }

        // Group trips by trip_headsign and stop sequence
        $groupedTrips = $enhancedTrips->groupBy(function ($trip) {
            return $trip->trip_headsign . '|' . implode(',', $trip->stop_sequence);
        })->map(function ($group) {
            return $group->first();
        });

        // If reference sequence, filter out trips with different sequences
        if ($referenceSequence) {
            $groupedTrips = $groupedTrips->filter(function ($trip) use ($referenceSequence) {
                return $trip->stop_sequence == $referenceSequence;
            });
        }

        // Determine the selected trip
        $trip = $tripId ? $enhancedTrips->where('trip_id', $tripId)->first() : $groupedTrips->first();

        // Fetch stops for the selected trip
        $stops = $trip ? DB::connection('mysql_trains')
            ->table('stops')
            ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $trip->trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.*', 'stop_times.stop_sequence']) : [];

        return Inertia::render('TrainDetails', [
            'route' => $route,
            'trips' => $groupedTrips->values(),
            'selectedTrip' => $trip,
            'stops' => $stops
        ]);
    }

    public function getStopTimes($stopId, Request $request)
    {
        \Log::info('getStopTimes called with:', [
            'stop_id' => $stopId,
            'route_id' => $request->query('route_id'),
            'trip_id' => $request->query('trip_id'),
            'date' => $request->query('date')
        ]);

        $routeId = $request->query('route_id');
        $tripId = $request->query('trip_id');
        $date = $request->query('date');

        if (!$routeId || !$tripId || !$date) {
            return response()->json([
                'error' => 'Missing parameters',
                'received' => [
                    'route_id' => $routeId,
                    'trip_id' => $tripId,
                    'date' => $date
                ]
            ]);
        }

        // Get the reference trip details
        $referenceTrip = DB::connection('mysql_trains')
            ->table('trips')
            ->where('trip_id', $tripId)
            ->first(['route_id', 'service_id', 'trip_headsign']);

        if (!$referenceTrip) {
            return response()->json([
                'error' => 'Trip not found',
                'trip_id' => $tripId
            ]);
        }

        // Get all trips for this route that follow the same stop pattern
        $referenceStopPattern = DB::connection('mysql_trains')
            ->table('stop_times')
            ->where('trip_id', $tripId)
            ->orderBy('stop_sequence')
            ->pluck('stop_id');

        // Find all trips that serve this route and have the same stop pattern
        $matchingTrips = DB::connection('mysql_trains')
            ->table('trips')
            ->where('route_id', $routeId)
            ->where(function ($query) use ($referenceStopPattern, $stopId) {
                $query->whereExists(function ($subquery) use ($referenceStopPattern, $stopId) {
                    $subquery->select(DB::raw(1))
                            ->from('stop_times')
                            ->whereColumn('stop_times.trip_id', 'trips.trip_id')
                            ->where('stop_id', $stopId);
                });
            })
            ->get(['trip_id', 'service_id', 'trip_headsign']);

        \Log::info('Found matching trips:', [
            'count' => $matchingTrips->count(),
            'trips' => $matchingTrips->toArray()
        ]);

        // Get all valid service IDs for the date
        $dateObj = \Carbon\Carbon::parse($date);
        $dayOfWeek = strtolower($dateObj->format('l'));
        $formattedDate = $dateObj->format('Ymd');

        // Get services from calendar
        $activeServiceIds = DB::connection('mysql_trains')
            ->table('calendar')
            ->where('start_date', '<=', $formattedDate)
            ->where('end_date', '>=', $formattedDate)
            ->where($dayOfWeek, 1)
            ->pluck('service_id');

        // Add services from calendar_dates (added service)
        $addedServiceIds = DB::connection('mysql_trains')
            ->table('calendar_dates')
            ->where('date', $formattedDate)
            ->where('exception_type', 1)
            ->pluck('service_id');

        // Remove services from calendar_dates (removed service)
        $removedServiceIds = DB::connection('mysql_trains')
            ->table('calendar_dates')
            ->where('date', $formattedDate)
            ->where('exception_type', 2)
            ->pluck('service_id');

        // Combine service IDs
        $validServiceIds = $activeServiceIds->merge($addedServiceIds)
            ->diff($removedServiceIds)
            ->unique()
            ->values();

        \Log::info('Valid service IDs:', [
            'count' => $validServiceIds->count(),
            'services' => $validServiceIds->toArray()
        ]);

        // Get all valid trips
        $validTripIds = $matchingTrips
            ->whereIn('service_id', $validServiceIds)
            ->pluck('trip_id');

        // Get stop times for all valid trips
        $stopTimes = DB::connection('mysql_trains')
            ->table('stop_times')
            ->whereIn('trip_id', $validTripIds)
            ->where('stop_id', $stopId)
            ->orderBy('departure_time')
            ->get(['arrival_time', 'departure_time', 'stop_sequence', 'trip_id']);

        \Log::info('Found stop times:', [
            'count' => $stopTimes->count(),
            'times' => $stopTimes->map(function($time) {
                return [
                    'trip_id' => $time->trip_id,
                    'departure' => $time->departure_time
                ];
            })->toArray()
        ]);

        if ($stopTimes->isEmpty()) {
            return response()->json([
                'error' => 'No stop times found',
                'debug' => [
                    'trip_id' => $tripId,
                    'stop_id' => $stopId,
                    'valid_trips_count' => $validTripIds->count(),
                    'valid_services_count' => $validServiceIds->count()
                ]
            ]);
        }

        // Get route information for each trip
        $tripRoutes = DB::connection('mysql_trains')
            ->table('stop_times')
            ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
            ->whereIn('stop_times.trip_id', $validTripIds)
            ->orderBy('stop_times.trip_id')
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.stop_name', 'stop_times.stop_sequence', 'stop_times.trip_id'])
            ->groupBy('trip_id');

        $result = $stopTimes->map(function ($time) use ($tripRoutes, $matchingTrips) {
            $stops = $tripRoutes[$time->trip_id] ?? collect();
            $firstStop = $stops->first();
            $lastStop = $stops->last();
            $trip = $matchingTrips->firstWhere('trip_id', $time->trip_id);

            return [
                'trip_id' => $time->trip_id,
                'arrival_time' => $this->formatGTFSTime($time->arrival_time),
                'departure_time' => $this->formatGTFSTime($time->departure_time),
                'from_station' => $firstStop ? $firstStop->stop_name : '',
                'to_station' => $lastStop ? $lastStop->stop_name : '',
                'headsign' => $trip ? $trip->trip_headsign : ''
            ];
        });

        return response()->json([
            'debug' => [
                'trip_id' => $tripId,
                'stop_id' => $stopId,
                'route_id' => $routeId,
                'date' => $date,
                'matching_trips_count' => $matchingTrips->count(),
                'valid_services_count' => $validServiceIds->count(),
                'stop_times_found' => $stopTimes->count()
            ],
            'data' => $result
        ]);
    }

    /**
     * Format GTFS time string properly
     */
    private function formatGTFSTime($time)
    {
        if (!$time) return null;

        // GTFS allows times greater than 24:00:00 for trips past midnight
        $parts = explode(':', $time);
        if (count($parts) !== 3) return $time;

        $hours = intval($parts[0]);
        $minutes = $parts[1];
        $seconds = $parts[2];

        // Keep hours as is - GTFS spec allows hours > 24 for trips past midnight
        return sprintf('%02d:%s:%s', $hours, $minutes, $seconds);
    }

    public function showTripTimes($tripId, Request $request)
    {
        // Get trip info
        $trip = DB::connection('mysql_trains')
            ->table('trips')
            ->where('trip_id', $tripId)
            ->first();

        if (!$trip) {
            abort(404);
        }

        // Get all stops for this trip with their times
        $stops = DB::connection('mysql_trains')
            ->table('stops')
            ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $tripId)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.*', 'stop_times.arrival_time', 'stop_times.departure_time']);

        return Inertia::render('TripTimes', [
            'trip' => $trip,
            'stops' => $stops,
            'selectedStopId' => $request->input('stop_id'),
            'selectedDepartureTime' => $request->input('departure_time')
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string'
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        // First, search by route names
        $routesByName = DB::connection('mysql_trains')
            ->table('trips')
            ->select([
                'routes.route_id',
                'routes.route_short_name',
                'routes.route_long_name',
                'trips.trip_id',
                'from_stop.stop_name as from_station',
                'to_stop.stop_name as to_station',
                'st1.departure_time',
                'st2.arrival_time'
            ])
            ->join('routes', 'trips.route_id', '=', 'routes.route_id')
            ->join('stop_times as st1', function($join) {
                $join->on('trips.trip_id', '=', 'st1.trip_id')
                    ->whereRaw('st1.stop_sequence = (SELECT MIN(stop_sequence) FROM stop_times WHERE trip_id = trips.trip_id)');
            })
            ->join('stops as from_stop', 'st1.stop_id', '=', 'from_stop.stop_id')
            ->join('stop_times as st2', function($join) {
                $join->on('trips.trip_id', '=', 'st2.trip_id')
                    ->whereRaw('st2.stop_sequence = (SELECT MAX(stop_sequence) FROM stop_times WHERE trip_id = trips.trip_id)');
            })
            ->join('stops as to_stop', 'st2.stop_id', '=', 'to_stop.stop_id')
            ->where(function($query) use ($from, $to) {
                $query->where('routes.route_long_name', 'LIKE', "%{$from}%-%{$to}%")
                    ->orWhere('routes.route_long_name', 'LIKE', "%{$from}% - %{$to}%")
                    ->orWhere('routes.route_long_name', 'LIKE', "%{$to}%-%{$from}%")
                    ->orWhere('routes.route_long_name', 'LIKE', "%{$to}% - %{$from}%");
            })
            ->distinct()
            ->get();

        // Then, search by stop sequence
        $routesByStops = DB::connection('mysql_trains')
            ->table('trips')
            ->select([
                'routes.route_id',
                'routes.route_short_name',
                'routes.route_long_name',
                'trips.trip_id',
                'from_stop.stop_name as from_station',
                'to_stop.stop_name as to_station',
                'st1.departure_time',
                'st2.arrival_time'
            ])
            ->join('routes', 'trips.route_id', '=', 'routes.route_id')
            ->join('stop_times as st1', function($join) {
                $join->on('trips.trip_id', '=', 'st1.trip_id');
            })
            ->join('stops as from_stop', 'st1.stop_id', '=', 'from_stop.stop_id')
            ->join('stop_times as st2', function($join) {
                $join->on('trips.trip_id', '=', 'st2.trip_id');
            })
            ->join('stops as to_stop', 'st2.stop_id', '=', 'to_stop.stop_id')
            ->where(function($query) use ($from, $to) {
                $query->where('from_stop.stop_name', 'LIKE', "%{$from}%")
                    ->where('to_stop.stop_name', 'LIKE', "%{$to}%")
                    ->whereColumn('st1.stop_sequence', '<', 'st2.stop_sequence');
            })
            ->distinct()
            ->get();

        // Combine both results
        $routes = $routesByName->concat($routesByStops);

        // Get all stops for each trip to show the complete route
        $routesWithStops = $routes->map(function($route) {
            // Get all stops for this trip in sequence
            $allStops = DB::connection('mysql_trains')
                ->table('stop_times')
                ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
                ->where('stop_times.trip_id', $route->trip_id)
                ->orderBy('stop_times.stop_sequence')
                ->get(['stops.stop_name', 'stop_times.departure_time', 'stop_times.arrival_time']);

            $route->all_stops = $allStops;
            return $route;
        });

        // Group routes by route_id to get unique routes with their trips
        $uniqueRoutes = $routesWithStops->unique('route_id')->map(function($route) {
            // Get all trips for this route
            $trips = DB::connection('mysql_trains')
                ->table('trips')
                ->where('route_id', $route->route_id)
                ->get(['trip_id']);

            // Keep the original trip_id from the route
            $route->trips = $trips->map(function($trip) {
                $stopTimes = DB::connection('mysql_trains')
                    ->table('stop_times')
                    ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
                    ->where('stop_times.trip_id', $trip->trip_id)
                    ->orderBy('stop_times.stop_sequence')
                    ->get(['stops.stop_name', 'stop_times.departure_time', 'stop_times.arrival_time']);

                return [
                    'trip_id' => $trip->trip_id,
                    'all_stops' => $stopTimes
                ];
            });

            // Ensure we keep the original trip_id from the route
            if (!$route->trip_id && $trips->isNotEmpty()) {
                $route->trip_id = $trips->first()->trip_id;
            }

            return $route;
        })->values();

        // If only one route found, redirect to its details
        if ($uniqueRoutes->count() === 1) {
            $route = $uniqueRoutes->first();
            if ($route->trip_id) {
                $fromStop = DB::connection('mysql_trains')
                    ->table('stops')
                    ->where('stop_name', 'LIKE', "%{$from}%")
                    ->first();
                if ($fromStop) {
                    return redirect()->to("/train/details/{$route->route_id}/{$route->trip_id}?selected_stop_id={$fromStop->stop_id}");
                }
            }
        }

        // Pass the search parameters in the URL for refresh handling
        return Inertia::render('SearchResults', [
            'routes' => $uniqueRoutes,
            'from' => $from,
            'to' => $to,
            'type' => 'train'
        ])->with([
            'url' => url()->current() . '?' . http_build_query([
                'from' => $from,
                'to' => $to,
                'type' => 'train'
            ])
        ]);
    }
}
