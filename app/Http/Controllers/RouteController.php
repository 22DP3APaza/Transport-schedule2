<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\StopTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class RouteController extends Controller
{
    public function searchRoute(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
            'type' => 'required|in:all,bus,trolleybus,tram,train'
        ]);

        // Get parameters from either POST or GET
        $from = $request->input('from') ?? $request->query('from');
        $to = $request->input('to') ?? $request->query('to');
        $type = $request->input('type') ?? $request->query('type');

        // If type is 'all', search in all transport types
        if ($type === 'all') {
            $allRoutes = collect();

            // Search in each transport type and tag routes with their type
            foreach (['bus', 'trol', 'tram'] as $transportType) {
                $routes = $this->findRoutesConnectingStops($from, $to, $transportType);
                // Tag each route with its transport type
                $routes = $routes->map(function ($route) use ($transportType) {
                    $route->transport_type = $transportType === 'trol' ? 'trolleybus' : $transportType;
                    return $route;
                });
                $allRoutes = $allRoutes->concat($routes);
            }

            // If only one route found, redirect to its details
            if ($allRoutes->count() === 1) {
                $route = $allRoutes->first();
                $trip = Trip::where('route_id', $route->route_id)->first();
                if ($trip) {
                    $fromStop = Stop::where('stop_name', 'LIKE', "%{$from}%")->first();
                    return redirect()->to("/route/details/{$route->route_id}/{$trip->trip_id}?selected_stop_id={$fromStop->stop_id}");
                }
            }

            return Inertia::render('SearchResults', [
                'routes' => $allRoutes,
                'from' => $from,
                'to' => $to,
                'type' => 'all'
            ])->with([
                'url' => url()->current() . '?' . http_build_query([
                    'from' => $from,
                    'to' => $to,
                    'type' => 'all'
                ])
            ]);
        }

        // For specific transport type
        $matchingRoutes = $this->findRoutesConnectingStops($from, $to, $type === 'trolleybus' ? 'trol' : $type);

        // If only one route found, redirect to its details
        if ($matchingRoutes->count() === 1) {
            $route = $matchingRoutes->first();
            $trip = Trip::where('route_id', $route->route_id)->first();
            if ($trip) {
                $fromStop = Stop::where('stop_name', 'LIKE', "%{$from}%")->first();
                return redirect()->to("/route/details/{$route->route_id}/{$trip->trip_id}?selected_stop_id={$fromStop->stop_id}");
            }
        }

        if ($matchingRoutes->isNotEmpty()) {
            return Inertia::render('SearchResults', [
                'routes' => $matchingRoutes,
                'from' => $from,
                'to' => $to,
                'type' => $type
            ])->with([
                'url' => url()->current() . '?' . http_build_query([
                    'from' => $from,
                    'to' => $to,
                    'type' => $type
                ])
            ]);
        }

        // If no routes found, return with suggestions
        return back()->with([
            'searchParams' => [
                'from' => $from,
                'to' => $to,
            ],
            'suggestions' => [
                'fromStopRoutes' => $this->findRoutesThroughStop($from, $type),
                'toStopRoutes' => $this->findRoutesThroughStop($to, $type),
            ]
        ]);
    }

    protected function findRoutesConnectingStops(string $from, string $to, string $type)
    {
        \Log::info('Searching for routes', ['from' => $from, 'to' => $to, 'type' => $type]);

        // Find stop IDs for both from and to stops with more flexible matching
        $fromStops = Stop::where('stop_name', 'LIKE', "%{$from}%")
            ->orWhere('stop_name', 'LIKE', str_replace('ļ', 'l', "%{$from}%"))
            ->orWhere('stop_name', 'LIKE', str_replace('ģ', 'g', "%{$from}%"))
            ->get();

        $toStops = Stop::where('stop_name', 'LIKE', "%{$to}%")
            ->orWhere('stop_name', 'LIKE', str_replace('ļ', 'l', "%{$to}%"))
            ->orWhere('stop_name', 'LIKE', str_replace('ģ', 'g', "%{$to}%"))
            ->get();

        \Log::info('From stops found:', ['stops' => $fromStops->pluck('stop_name')]);
        \Log::info('To stops found:', ['stops' => $toStops->pluck('stop_name')]);

        $matchingRoutes = collect();

        // First try to find routes by route names and trip headsigns
        $routeQuery = Route::where('route_id', 'LIKE', "%{$type}%");

        // Search in route_long_name
        $routesByName = $routeQuery->where(function ($query) use ($from, $to) {
            // Format the search terms to handle both "-" and " - " formats
            $fromEscaped = str_replace(['%', '_'], ['\%', '\_'], $from);
            $toEscaped = str_replace(['%', '_'], ['\%', '\_'], $to);

            $query->where(function ($q) use ($fromEscaped, $toEscaped) {
                // Forward direction
                $q->where('route_long_name', 'LIKE', "%{$fromEscaped}%-%{$toEscaped}%")
                  ->orWhere('route_long_name', 'LIKE', "%{$fromEscaped}% - %{$toEscaped}%");
            })
            ->orWhere(function ($q) use ($fromEscaped, $toEscaped) {
                // Reverse direction
                $q->where('route_long_name', 'LIKE', "%{$toEscaped}%-%{$fromEscaped}%")
                  ->orWhere('route_long_name', 'LIKE', "%{$toEscaped}% - %{$fromEscaped}%");
            });
        })->get();

        \Log::info('Routes found by name:', ['routes' => $routesByName->pluck('route_long_name')]);
        $matchingRoutes = $matchingRoutes->concat($routesByName);

        // If no routes found by name, try finding by stop sequence
        if ($matchingRoutes->isEmpty() && !$fromStops->isEmpty() && !$toStops->isEmpty()) {
            $fromStopIds = $fromStops->pluck('stop_id');
            $toStopIds = $toStops->pluck('stop_id');

            $routesByStops = Route::whereIn('route_id', function ($query) use ($fromStopIds, $toStopIds) {
                $query->select('trips.route_id')
                    ->from('trips')
                    ->join('stop_times as st1', 'trips.trip_id', '=', 'st1.trip_id')
                    ->join('stop_times as st2', function ($join) {
                        $join->on('st2.trip_id', '=', 'st1.trip_id');
                    })
                    ->whereIn('st1.stop_id', $fromStopIds)
                    ->whereIn('st2.stop_id', $toStopIds)
                    ->where('st2.stop_sequence', '>', 'st1.stop_sequence')
                    ->distinct();
            })
            ->where('route_id', 'LIKE', "%{$type}%")
            ->get();

            \Log::info('Routes found by stop sequence:', ['routes' => $routesByStops->pluck('route_long_name')]);
            $matchingRoutes = $matchingRoutes->concat($routesByStops);
        }

        // If still no routes found, try finding by trip headsigns
        if ($matchingRoutes->isEmpty()) {
            $routesByTrip = Route::where('route_id', 'LIKE', "%{$type}%")
                ->whereExists(function ($query) use ($from, $to) {
                    $query->select('trips.trip_id')
                        ->from('trips')
                        ->whereColumn('trips.route_id', 'routes.route_id')
                        ->where(function ($q) use ($from, $to) {
                            // Forward direction
                            $q->where('trips.trip_headsign', 'LIKE', "%{$from}%-%{$to}%")
                              ->orWhere('trips.trip_headsign', 'LIKE', "%{$from}% - %{$to}%")
                              // Reverse direction
                              ->orWhere('trips.trip_headsign', 'LIKE', "%{$to}%-%{$from}%")
                              ->orWhere('trips.trip_headsign', 'LIKE', "%{$to}% - %{$from}%");
                        });
                })
                ->get();

            \Log::info('Routes found by trip headsign:', ['routes' => $routesByTrip->pluck('route_long_name')]);
            $matchingRoutes = $matchingRoutes->concat($routesByTrip);
        }

        $matchingRoutes = $matchingRoutes->unique('route_id');
        \Log::info('Final matching routes:', ['routes' => $matchingRoutes->pluck('route_long_name')]);

        return $matchingRoutes;
    }

    protected function findRoutesThroughStop(string $stopName, string $type)
    {
        $stopIds = Stop::where('stop_name', 'LIKE', $stopName)->pluck('stop_id');

        if ($stopIds->isEmpty()) {
            return collect();
        }

        return Route::whereIn('route_id', function ($query) use ($stopIds) {
                $query->select('trips.route_id')
                    ->from('trips')
                    ->join('stop_times', 'trips.trip_id', '=', 'stop_times.trip_id')
                ->whereIn('stop_times.stop_id', $stopIds);
            })
        ->where('route_id', 'LIKE', "%{$type}%")
            ->get();
    }

    protected function extractStopsFromRouteName(string $routeName): array
    {
        if (strpos($routeName, ' - ') === false) {
            return [$routeName];
        }
        return array_map('trim', explode(' - ', $routeName));
    }

    public function searchBus(Request $request)
{
    $request->validate([
        'from' => 'required|string',
        'to' => 'required|string'
    ]);

        $from = $request->input('from') ?? $request->query('from');
        $to = $request->input('to') ?? $request->query('to');

        // Find all routes that connect these stops
        $routes = $this->findRoutesConnectingStops($from, $to, 'bus');

        // If only one route found, redirect to its details
        if ($routes->count() === 1) {
            $route = $routes->first();
            $trip = Trip::where('route_id', $route->route_id)->first();
            if ($trip) {
                $fromStop = Stop::where('stop_name', 'LIKE', "%{$from}%")->first();
                return redirect()->to("/route/details/{$route->route_id}/{$trip->trip_id}?selected_stop_id={$fromStop->stop_id}");
            }
        }

        // Pass the search parameters in the URL for refresh handling
        return Inertia::render('SearchResults', [
            'routes' => $routes,
            'from' => $from,
            'to' => $to,
            'type' => 'bus'
        ])->with([
            'url' => url()->current() . '?' . http_build_query([
                'from' => $from,
                'to' => $to,
                'type' => 'bus'
            ])
        ]);
    }

    public function searchTram(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string'
        ]);

        $from = $request->input('from') ?? $request->query('from');
        $to = $request->input('to') ?? $request->query('to');

        // Find all routes that connect these stops
        $routes = $this->findRoutesConnectingStops($from, $to, 'tram');

        // If only one route found, redirect to its details
        if ($routes->count() === 1) {
            $route = $routes->first();
            $trip = Trip::where('route_id', $route->route_id)->first();
            if ($trip) {
                $fromStop = Stop::where('stop_name', 'LIKE', "%{$from}%")->first();
                return redirect()->to("/route/details/{$route->route_id}/{$trip->trip_id}?selected_stop_id={$fromStop->stop_id}");
            }
        }

        // Pass the search parameters in the URL for refresh handling
        return Inertia::render('SearchResults', [
            'routes' => $routes,
            'from' => $from,
            'to' => $to,
            'type' => 'tram'
        ])->with([
            'url' => url()->current() . '?' . http_build_query([
                'from' => $from,
                'to' => $to,
                'type' => 'tram'
            ])
        ]);
    }

    public function searchTrolleybus(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string'
        ]);

        $from = $request->input('from') ?? $request->query('from');
        $to = $request->input('to') ?? $request->query('to');

        // Find all routes that connect these stops
        $routes = $this->findRoutesConnectingStops($from, $to, 'trol');

        // If only one route found, redirect to its details
        if ($routes->count() === 1) {
            $route = $routes->first();
            $trip = Trip::where('route_id', $route->route_id)->first();
            if ($trip) {
                $fromStop = Stop::where('stop_name', 'LIKE', "%{$from}%")->first();
                return redirect()->to("/route/details/{$route->route_id}/{$trip->trip_id}?selected_stop_id={$fromStop->stop_id}");
            }
        }

        // Pass the search parameters in the URL for refresh handling
        return Inertia::render('SearchResults', [
            'routes' => $routes,
            'from' => $from,
            'to' => $to,
            'type' => 'trolleybus'
        ])->with([
            'url' => url()->current() . '?' . http_build_query([
                'from' => $from,
                'to' => $to,
                'type' => 'trolleybus'
            ])
        ]);
    }

    public function getPossibleDestinations(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'type' => 'required|in:all,bus,trolleybus,tram,train'
        ]);

        $from = $request->input('from');
        $type = $request->input('type');

        // Find the stop ID for the 'from' stop with more flexible matching
        $fromStops = Stop::where('stop_name', 'LIKE', "%{$from}%")
            ->orWhere('stop_name', 'LIKE', str_replace('ļ', 'l', "%{$from}%"))
            ->orWhere('stop_name', 'LIKE', str_replace('ģ', 'g', "%{$from}%"))
            ->get();

        $possibleDestinations = collect();

        // Get destinations from stop_times
        if (!$fromStops->isEmpty()) {
            $fromStopIds = $fromStops->pluck('stop_id');

            // Handle 'all' transport type
            if ($type === 'all') {
                foreach (['bus', 'trol', 'tram'] as $transportType) {
                    $destinations = $this->getDestinationsForType($fromStopIds, $transportType);
                    $possibleDestinations = $possibleDestinations->concat($destinations);
                }
            } else {
                // For specific transport type
                $typeForQuery = $type === 'trolleybus' ? 'trol' : $type;
                $possibleDestinations = $this->getDestinationsForType($fromStopIds, $typeForQuery);
            }
        }

        // Get destinations from route names
        $routeQuery = Route::query();
        if ($type !== 'all') {
            $typeForQuery = $type === 'trolleybus' ? 'trol' : $type;
            $routeQuery->where('route_id', 'LIKE', "%{$typeForQuery}%");
        }

        // Find routes that have the from stop in their name
        $routes = $routeQuery->where(function ($query) use ($from) {
            $query->where('route_long_name', 'LIKE', "%{$from}%-%")
                ->orWhere('route_long_name', 'LIKE', "%{$from}% - %")
                ->orWhere('route_long_name', 'LIKE', "%-{$from}%")
                ->orWhere('route_long_name', 'LIKE', "% - {$from}%");
        })->get();

        // Extract destination stops from route names
        foreach ($routes as $route) {
            $parts = array_map('trim', explode('-', str_replace(' - ', '-', $route->route_long_name)));
            foreach ($parts as $part) {
                if (stripos($part, $from) === false) {
                    // This is a potential destination
                    $possibleDestinations->push([
                        'stop_id' => null,
                        'stop_name' => trim($part),
                        'from_route' => true
                    ]);
                }
            }
        }

        // Also check trip headsigns
        $trips = Trip::whereIn('route_id', $routes->pluck('route_id'))
            ->whereNotNull('trip_headsign')
            ->get();

        foreach ($trips as $trip) {
            if (!empty($trip->trip_headsign)) {
                $parts = array_map('trim', explode('-', str_replace(' - ', '-', $trip->trip_headsign)));
                foreach ($parts as $part) {
                    if (stripos($part, $from) === false) {
                        $possibleDestinations->push([
                            'stop_id' => null,
                            'stop_name' => trim($part),
                            'from_route' => true
                        ]);
                    }
                }
            }
        }

        // Remove duplicates and return
        return response()->json(
            $possibleDestinations->unique(function ($item) {
                return strtolower($item instanceof Stop ? $item->stop_name : $item['stop_name']);
            })->values()
        );
    }

    protected function getDestinationsForType($fromStopIds, $type)
    {
        return Stop::whereIn('stop_id', function ($query) use ($fromStopIds, $type) {
            $query->select('st2.stop_id')
                ->from('stop_times as st1')
                ->join('stop_times as st2', 'st1.trip_id', '=', 'st2.trip_id')
                ->join('trips', 'st1.trip_id', '=', 'trips.trip_id')
                ->join('routes', 'trips.route_id', '=', 'routes.route_id')
                ->whereIn('st1.stop_id', $fromStopIds)
                ->where('routes.route_id', 'LIKE', "%{$type}%")
                ->where('st2.stop_sequence', '>', 'st1.stop_sequence')
                ->whereRaw('st2.stop_id != st1.stop_id');
        })
        ->distinct()
        ->get(['stop_id', 'stop_name']);
}
}
