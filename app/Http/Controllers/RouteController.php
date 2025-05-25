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
            'type' => 'required|in:bus,trolleybus,tram,train'
        ]);

        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type');

        // 1. First try exact match with route_long_name
        $routeLongName = "{$from} - {$to}";
        $route = Route::where('route_long_name', $routeLongName)
                    ->where('route_id', 'LIKE', "%{$type}%")
                    ->first();

        if ($route) {
            return Inertia::location(route('route.details', ['route_id' => $route->route_id]));
        }

        // 2. If no exact match, find routes that connect these stops
        $matchingRoutes = $this->findRoutesConnectingStops($from, $to, $type);

        if ($matchingRoutes->isNotEmpty()) {
            // Redirect to the first matching route
            $route = $matchingRoutes->first();
            return Inertia::location(route('route.details', ['route_id' => $route->route_id]));
        }

        // 3. If still no match, redirect back with suggestions
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
        return Route::where('route_id', 'LIKE', "%{$type}%")
            ->whereNotIn('route_id', function($query) {
                $query->select('route_id')
                    ->from('routes')
                    ->where('route_id', 'LIKE', '%_x%');
            })
            ->where('route_short_name', '!=', '99')
            ->get()
            ->filter(function ($route) use ($from, $to) {
                $stops = $this->extractStopsFromRouteName($route->route_long_name);
                $fromIndex = array_search($from, $stops);
                $toIndex = array_search($to, $stops);
                return $fromIndex !== false && $toIndex !== false && $fromIndex < $toIndex;
            });
    }

    protected function findRoutesThroughStop(string $stopName, string $type)
    {
        // First check route names
        $routesFromNames = Route::where('route_id', 'LIKE', "%{$type}%")
            ->where(function($query) use ($stopName) {
                $query->where('route_long_name', 'LIKE', "%{$stopName}%")
                      ->orWhere('route_long_name', 'LIKE', "%" . Str::slug($stopName) . "%");
            })
            ->get()
            ->filter(function($route) use ($stopName) {
                $stops = $this->extractStopsFromRouteName($route->route_long_name);
                return in_array($stopName, $stops);
            });

        // Then check actual stop_times data
        $routesFromStops = Route::where('route_id', 'LIKE', "%{$type}%")
            ->whereIn('route_id', function($query) use ($stopName) {
                $query->select('trips.route_id')
                    ->from('trips')
                    ->join('stop_times', 'trips.trip_id', '=', 'stop_times.trip_id')
                    ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
                    ->where('stops.stop_name', $stopName);
            })
            ->get();

        return $routesFromNames->merge($routesFromStops)->unique('route_id');
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

    $from = $request->input('from');
    $to = $request->input('to');

    // 1. First try exact match with route_long_name
    $routeLongName = "{$from} - {$to}";
    $route = Route::where('route_long_name', $routeLongName)
                ->where('route_id', 'LIKE', '%bus%')
                ->first();

    if ($route) {
        return Inertia::location(route('route.details', ['route_id' => $route->route_id]));
    }

    // 2. If no exact match, find routes that connect these stops
    $matchingRoutes = $this->findRoutesConnectingStops($from, $to, 'bus');

    if ($matchingRoutes->isNotEmpty()) {
        $route = $matchingRoutes->first();
        return Inertia::location(route('route.details', ['route_id' => $route->route_id]));
    }

    // 3. If still no match, redirect back with suggestions
    return back()->with([
        'searchParams' => [
            'from' => $from,
            'to' => $to,
        ],
        'suggestions' => [
            'fromStopRoutes' => $this->findRoutesThroughStop($from, 'bus'),
            'toStopRoutes' => $this->findRoutesThroughStop($to, 'bus'),
        ]
    ]);
}
}
