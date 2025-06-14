<?php

use App\Http\Controllers\GraphicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TrainController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CSVImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Route as ModelRoute;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\SavedRouteController;
use App\Http\Controllers\StopTimeController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\NewsController;

// Home Page
Route::get('/', function () {
    return Inertia::render('home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Route: Bus
Route::get('/bus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%bus%')
                        ->where('route_id', 'NOT LIKE', '%_x%') // Ignore "_x" variants
                        ->where('route_short_name', '!=', '99') // Ignore route 99
                        ->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('bus', compact('routes'));
});

// Route: Trolleybus
Route::get('/trolleybus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%trol%')
                        ->where('route_short_name', '!=', '99') // Ignore route 99
                        ->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('trolleybus', compact('routes'));
});

// Route: Tram
Route::get('/tram', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%tram%')
                        ->where('route_short_name', '!=', '99') // Ignore route 99
                        ->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('tram', compact('routes'));
});

// Route: Train
Route::get('/train', [TrainController::class, 'index']);
Route::post('/train/search', [TrainController::class, 'search']);
Route::get('/train/details/{routeId}/{tripId}', [TrainController::class, 'showDetails']);
Route::get('/train/trip/{tripId}', [TrainController::class, 'showTripTimes']);

// Login Page
Route::get('/login', function () {
    return Inertia::render('login');
});

// Test Page
Route::get('/test', function () {
    return Inertia::render('Test');
});

// Settings Page
Route::get('/settings', function () {
    return Inertia::render('settings');
});

// Route Details Page
Route::get('/route/details/{route_id}/{trip_id?}', function (Request $request, $route_id, $trip_id = null) {
    $database = $request->query('database');

    // If a specific database is requested, use that connection
    if ($database) {
        $db = DB::connection($database);

        // Fetch the route details
        $route = $db->table('routes')
            ->where('route_id', $route_id)
            ->first(['route_id', 'route_short_name', 'route_long_name', 'route_color']);

        if (!$route) {
            abort(404);
        }

        // Fetch all trips for the route
        $trips = $db->table('trips')
            ->where('route_id', $route_id)
            ->get();

        $enhancedTrips = $trips->map(function ($trip) use ($db) {
            // Get all stops for this trip with their sequence
            $tripStops = $db->table('stop_times')
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
    } else {
        // Fetch the route details from the default database
        $route = ModelRoute::where('route_id', $route_id)
            ->firstOrFail(['route_id', 'route_short_name', 'route_long_name', 'route_color']);

        // Fetch all trips for the route
        $trips = Trip::where('route_id', $route_id)->get();

        $enhancedTrips = $trips->map(function ($trip) {
            // Get all stops for this trip with their sequence
            $tripStops = DB::table('stop_times')
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
    }

    // If selected trip, use its stop sequence as reference
    $referenceSequence = null;
    if ($trip_id) {
        $selectedTrip = $enhancedTrips->firstWhere('trip_id', $trip_id);
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
    $trip = $trip_id ? $enhancedTrips->where('trip_id', $trip_id)->first() : $groupedTrips->first();

    // Fetch stops for the selected trip
    if ($trip) {
        if ($database) {
            $db = DB::connection($database);
            $stops = $db->table('stops')
                ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
                ->where('stop_times.trip_id', $trip->trip_id)
                ->orderBy('stop_times.stop_sequence')
                ->get(['stops.*', 'stop_times.stop_sequence']);
        } else {
            $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
                ->where('stop_times.trip_id', $trip->trip_id)
                ->orderBy('stop_times.stop_sequence')
                ->get(['stops.*', 'stop_times.stop_sequence']);
        }
    } else {
        $stops = [];
    }

    // Add debug information
    if ($database) {
        \Log::info('Route details:', [
            'route_id' => $route_id,
            'trip_id' => $trip_id,
            'trips_count' => $trips->count(),
            'enhanced_trips_count' => $enhancedTrips->count(),
            'stops_count' => count($stops),
            'selected_trip' => $trip ? $trip->trip_id : null,
            'database' => $database
        ]);
    }

    // Render the Inertia view with the data
    return Inertia::render('RouteDetails', [
        'route' => $route,
        'trips' => $groupedTrips->values(),
        'selectedTrip' => $trip,
        'stops' => $stops,
        'database' => $database, // Pass the database name to the frontend
    ]);
})->name('route.details');

// Get Stops for a Specific Trip
Route::get('/route/details/{route_id}/{trip_id}/stops', function ($route_id, $trip_id, Request $request) {
    $database = $request->query('database');

    if ($database) {
        $db = DB::connection($database);
        $stops = $db->table('stops')
            ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.*', 'stop_times.arrival_time', 'stop_times.departure_time']);

        // Add debug logging
        \Log::info('Fetching Liepaja stops:', [
            'route_id' => $route_id,
            'trip_id' => $trip_id,
            'stops_count' => $stops->count(),
            'database' => $database
        ]);
    } else {
        $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.*', 'stop_times.arrival_time', 'stop_times.departure_time']);
    }

    return response()->json($stops);
})->name('route.trip.stops');

// Get routes with optional database parameter
Route::get('/api/routes', function (Request $request) {
    $type = $request->query('type');
    $database = $request->query('database');

    // If a specific database is requested, use that connection
    if ($database) {
        $query = DB::connection($database)->table('routes');
    } else {
        $query = Route::query();
    }

    if ($type) {
        if ($type === 'trolleybus') {
            $query->where('route_id', 'LIKE', '%trol%');
        } else {
            $query->where('route_id', 'LIKE', '%' . $type . '%');
        }
    }

    $query->where('route_short_name', '!=', '99'); // Ignore route 99

    return response()->json(
        $query->get(['route_id', 'route_short_name', 'route_long_name', 'route_color'])
    );
});

// Get Stop Times for a Specific Stop and Route
Route::get('/stop/times/{stop_id}', function (Request $request, $stop_id) {
    Log::info('Request received for stop_id: ' . $stop_id . ', route_id: ' . $request->query('route_id') . ', type: ' . $request->query('type', 'workdays'));

    $type = $request->query('type', 'workdays');
    $route_id = $request->query('route_id');
    $trip_id = $request->query('trip_id');
    $database = $request->query('database');

    if (!$route_id || !$trip_id) {
        Log::error('Route ID or Trip ID is missing');
        return response()->json(['error' => 'Route ID and Trip ID are required'], 400);
    }

    // Use the specified database connection if provided
    if ($database) {
        $db = DB::connection($database);
    } else {
        $db = DB::connection();
    }

    // Get the reference trip's stops sequence
    $referenceStops = $db->table('stop_times')
        ->where('trip_id', $trip_id)
        ->orderBy('stop_sequence')
        ->pluck('stop_id', 'stop_sequence');

    if ($referenceStops->isEmpty()) {
        return response()->json([]);
    }

    // === Determine active service IDs for the route ===
    $calendarQuery = $db->table('calendar');

    if ($type === 'weekends') {
        $calendarQuery->where(function ($query) {
            $query->where('saturday', 1)->orWhere('sunday', 1);
        });
    } else {
        $calendarQuery->where('monday', 1)
            ->where('tuesday', 1)
            ->where('wednesday', 1)
            ->where('thursday', 1)
            ->where('friday', 1);
    }

    $activeServiceIds = $calendarQuery->pluck('service_id');
    $today = now()->format('Ymd');

    $addedServices = $db->table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 1)
        ->pluck('service_id');

    $removedServices = $db->table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 2)
        ->pluck('service_id');

    $finalServiceIds = $activeServiceIds->merge($addedServices)->unique()->diff($removedServices);

    if ($finalServiceIds->isEmpty()) {
        return response()->json([]);
    }

    // Get all trips for this route with active services
    $potentialTrips = $db->table('trips')
        ->where('route_id', $route_id)
        ->whereIn('service_id', $finalServiceIds)
        ->pluck('trip_id');

    // Filter trips to only those that have exactly the same stops in the same sequence
    $matchingTripIds = collect();
    foreach ($potentialTrips as $potentialTripId) {
        $tripStops = $db->table('stop_times')
            ->where('trip_id', $potentialTripId)
            ->orderBy('stop_sequence')
            ->pluck('stop_id', 'stop_sequence');

        if ($tripStops->count() === $referenceStops->count() &&
            $tripStops->toArray() === $referenceStops->toArray()) {
            $matchingTripIds->push($potentialTripId);
        }
    }

    if ($matchingTripIds->isEmpty()) {
        return response()->json([]);
    }

    // === Fetch stop_times for the matching trips and the specific stop ===
    $stopTimes = $db->table('stop_times')
        ->whereIn('trip_id', $matchingTripIds)
        ->where('stop_id', $stop_id)
        ->orderBy('departure_time')
        ->get(['departure_time', 'trip_id'])
        ->map(function ($item) {
            if (str_starts_with($item->departure_time, '24:')) {
                $item->original_departure_time = $item->departure_time;
                $item->departure_time = preg_replace('/^24:/', '00:', $item->departure_time);
                $item->was_24 = true;
            } else {
                $item->was_24 = false;
            }
            return $item;
        })
        ->sortBy(function ($item) {
            return $item->departure_time;
        })
        ->sortByDesc('was_24')
        ->values()
        ->map(function ($item) {
            return [
                'departure_time' => $item->original_departure_time ?? $item->departure_time,
                'trip_id' => $item->trip_id
            ];
        });

    return response()->json($stopTimes);
})->name('stop.times');

// Get Stop Times by Departure Time
Route::get('/stoptimes', function (Request $request) {
    $trip_id = $request->query('trip_id');
    $stop_id = $request->query('stop_id');
    $departure_time = $request->query('departure_time');
    $database = $request->query('database');

    Log::info('Request received for /stoptimes. Specific Trip ID: ' . $trip_id . ', Stop ID: ' . $stop_id . ', Departure Time: ' . $departure_time . ', Database: ' . $database);

    if (!$trip_id || !$stop_id || !$departure_time) {
        Log::error('Missing parameters for /stoptimes request. Required: trip_id, stop_id, departure_time.');
        return response()->json(['error' => 'Trip ID, Stop ID and Departure Time are required'], 400);
    }

    // Use the specified database connection if provided
    if ($database) {
        $db = DB::connection($database);
    } else {
        $db = DB::connection();
    }

    // Fetch the route_id associated with the provided trip_id
    $tripDetails = $db->table('trips')
        ->where('trip_id', $trip_id)
        ->first(['route_id', 'shape_id']);

    if (!$tripDetails) {
        Log::warning('Trip not found for provided trip ID: ' . $trip_id);
        return response()->json([]);
    }

    $foundRouteId = $tripDetails->route_id;
    $foundShapeId = $tripDetails->shape_id;

    //fetches all stop times for this specific provided trip_id
    $stopTimes = $db->table('stop_times')
        ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
        ->where('stop_times.trip_id', $trip_id) // Use the specific trip_id directly
        ->orderBy('stop_times.stop_sequence')
        ->get([
            'stops.stop_name',
            'stops.stop_id',
            'stop_times.arrival_time',
            'stop_times.departure_time',
            'stop_times.stop_sequence',
            'stop_times.trip_id'
        ]);

    if ($stopTimes->isEmpty()) {
        Log::error('No stop times found for the provided trip ID: ' . $trip_id);
        return response()->json([]);
    }
    Log::info('Stop times fetched for provided trip: ' . $stopTimes->toJson());

    $groupedByTrip = $stopTimes->groupBy('trip_id');

    return Inertia::render('StopTimes', [
        'trips' => $groupedByTrip,
        'initialTripId' => $trip_id, // Use the specific trip ID passed
        'routeId' => $foundRouteId,
        'selectedStopId' => $stop_id,
        'selectedDepartureTime' => $departure_time,
        'database' => $database // Pass the database to the frontend
    ]);
})->name('stoptimes');

Route::get('/route/map/{route_id}/{trip_id}', function (Request $request, $route_id, $trip_id) {
    $database = $request->query('database');

    if ($database) {
        $db = DB::connection($database);

        // Get route info from the specified database
        $route = $db->table('routes')
            ->where('route_id', $route_id)
            ->first(['route_id', 'route_short_name', 'route_long_name', 'route_color']);

        if (!$route) {
            abort(404);
        }

        // Get trip info to find the shape
        $trip = $db->table('trips')
            ->where('trip_id', $trip_id)
            ->first(['trip_id', 'shape_id', 'trip_headsign']);

        if (!$trip) {
            abort(404);
        }

        // Get all shape points for this route
        $shapePoints = $db->table('shapes')
            ->where('shape_id', $trip->shape_id)
            ->orderBy('shape_pt_sequence')
            ->get(['shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence']);

        // Get all stops with their coordinates for this trip
        $stops = $db->table('stops')
            ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->where('stop_times.trip_id', $trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->get(['stops.stop_id', 'stops.stop_name', 'stops.stop_lat', 'stops.stop_lon', 'stop_times.stop_sequence']);

        // Get all GTFS stops
        $allStops = $db->table('stops')
            ->orderBy('stop_name')
            ->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);
    } else {
        // Original code for default database
    $route = ModelRoute::where('route_id', $route_id)->firstOrFail([
        'route_id', 'route_short_name', 'route_long_name', 'route_color'
    ]);

    $trip = Trip::where('trip_id', $trip_id)->firstOrFail([
        'trip_id', 'shape_id', 'trip_headsign'
    ]);

    $shapePoints = DB::table('shapes')
        ->where('shape_id', $trip->shape_id)
        ->orderBy('shape_pt_sequence')
        ->get(['shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence']);

    $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.stop_id', 'stops.stop_name', 'stops.stop_lat', 'stops.stop_lon', 'stop_times.stop_sequence']);

    $allStops = Stop::orderBy('stop_name')->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);
    }

    return Inertia::render('RouteMap', [
        'route' => $route,
        'trip' => $trip,
        'shapePoints' => $shapePoints,
        'stops' => $stops,
        'allStops' => $allStops,
        'database' => $database // Pass the database parameter to the frontend
    ]);
})->name('route.map');

// Import routes
Route::post('/import/{type}', [GraphicController::class, 'importExcelData']);

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::delete('/settings', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::get('/api/stops', function (Request $request) {
    $database = $request->query('database');
    $withCoordinates = $request->query('with_coordinates', false);
    $type = $request->query('type');

    // Define the columns to select
    $columns = $withCoordinates ?
        ['stops.stop_id', 'stops.stop_name', 'stops.stop_lat', 'stops.stop_lon'] :
        ['stops.stop_id', 'stops.stop_name'];

    // If a specific database is requested, use that connection
    if ($database) {
        $query = DB::connection($database)->table('stops');
    } else {
        $query = DB::table('stops');
    }

    // Join with stop_times and trips to get stops that are actually used by the transport type
    if ($type && $type !== 'all') {
        $typePattern = $type === 'trolleybus' ? 'trol' : $type;
        $query->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
              ->join('trips', 'stop_times.trip_id', '=', 'trips.trip_id')
              ->join('routes', 'trips.route_id', '=', 'routes.route_id')
              ->where('routes.route_id', 'LIKE', '%' . $typePattern . '%')
              ->distinct();
    }

    // Select the appropriate columns
    $query->select($columns);

    // Order by stop name
    $query->orderBy('stops.stop_name');

    $stops = $query->get();

    return response()->json($stops);
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::put('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggleAdmin');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
    Route::get('/news', [App\Http\Controllers\Admin\NewsController::class, 'index'])->name('news');
    Route::post('/news/scrape', [App\Http\Controllers\Admin\NewsController::class, 'scrape'])->name('news.scrape');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/saved-routes', [SavedRouteController::class, 'store'])->name('saved-routes.store');
    Route::get('/saved-routes', [SavedRouteController::class, 'index'])->name('saved-routes.index');
    Route::post('/save-stop-times', [StopTimeController::class, 'store'])->name('stop-times.store');
});

// GTFS Data Import Routes
Route::post('/import-shapes', [GraphicController::class, 'importShapes'])->name('import.shapes');
Route::post('/import-calendar', [GraphicController::class, 'importCalendar'])->name('import.calendar');
Route::post('/import-stops', [GraphicController::class, 'importStops'])->name('import.stops');
Route::post('/import-routes', [GraphicController::class, 'importRoutes'])->name('import.routes');
Route::post('/import-calendar-dates', [GraphicController::class, 'importCalendarDates'])->name('import.calendar_dates');
Route::post('/import-trips', [GraphicController::class, 'importTrips'])->name('import.trips');
Route::post('/import-stop-times', [GraphicController::class, 'importStopTimes'])->name('import.stop_times');

require __DIR__.'/auth.php';

// Additional Controllers
Route::match(['get', 'post'], '/search-route', [RouteController::class, 'searchRoute'])->name('searchRoute');
Route::get('/route/{id}', [RouteController::class, 'show'])->name('route.show');

// Stop search endpoints
Route::get('/api/possible-destinations', function (Request $request) {
    $from = $request->query('from');
    $type = $request->query('type');
    $database = $request->query('database');

    if (!$from) {
        return response()->json([]);
    }

    // Use the specified database connection if provided
    if ($database) {
        $db = DB::connection($database);

        // First, find all routes that pass through the 'from' stop
        $fromStop = $db->table('stops')
            ->where('stop_name', $from)
            ->first();

        if (!$fromStop) {
            return response()->json([]);
        }

        // Get all trips that pass through this stop
        $trips = $db->table('stop_times')
            ->where('stop_id', $fromStop->stop_id)
            ->join('trips', 'stop_times.trip_id', '=', 'trips.trip_id');

        if ($type) {
            if ($type === 'trolleybus') {
                $trips->where('trips.route_id', 'LIKE', '%trol%');
            } else {
                $trips->where('trips.route_id', 'LIKE', '%' . $type . '%');
            }
        }

        $tripIds = $trips->pluck('trips.trip_id');

        // Get all stops that are part of these trips
        $possibleStops = $db->table('stops')
            ->join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->whereIn('stop_times.trip_id', $tripIds)
            ->where('stops.stop_name', '!=', $from)
            ->select('stops.stop_id', 'stops.stop_name')
            ->distinct()
            ->get();

    } else {
        // Use default connection for Riga
        $fromStop = Stop::where('stop_name', $from)->first();

        if (!$fromStop) {
            return response()->json([]);
        }

        $trips = StopTime::where('stop_id', $fromStop->stop_id)
            ->join('trips', 'stop_times.trip_id', '=', 'trips.trip_id');

        if ($type) {
            if ($type === 'trolleybus') {
                $trips->where('trips.route_id', 'LIKE', '%trol%');
            } else {
                $trips->where('trips.route_id', 'LIKE', '%' . $type . '%');
            }
        }

        $tripIds = $trips->pluck('trips.trip_id');

        $possibleStops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
            ->whereIn('stop_times.trip_id', $tripIds)
            ->where('stops.stop_name', '!=', $from)
            ->select('stops.stop_id', 'stops.stop_name')
            ->distinct()
            ->get();
    }

    return response()->json($possibleStops);
});

Route::match(['get', 'post'], '/bus/search', [RouteController::class, 'searchBus'])->name('bus.search');
Route::match(['get', 'post'], '/trolleybus/search', [RouteController::class, 'searchTrolleybus'])->name('trolleybus.search');
Route::match(['get', 'post'], '/tram/search', [RouteController::class, 'searchTram'])->name('tram.search');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/save-stop-times', [SavedRouteController::class, 'store'])->name('save.stop.times');
    Route::get('/my-saved-times', [SavedRouteController::class, 'index'])->name('my.saved.times');
    Route::delete('/my-saved-times/{id}', [SavedRouteController::class, 'destroy'])->name('my.saved.times.delete');
});

Route::get('/route/details/{route_id}/{stop_id}/pdf', [PDFController::class, 'downloadStopTimes'])->name('route.stoptimes.pdf');

Route::get('/api/calendar-dates/{trip_id}', function ($trip_id) {
    $trip = Trip::where('trip_id', $trip_id)->first();
    if (!$trip) {
        return response()->json([]);
    }

    // Get calendar dates for this service
    $calendarDates = DB::table('calendar_dates')
        ->where('service_id', $trip->service_id)
        ->get(['date', 'exception_type']);

    return response()->json($calendarDates);
})->name('api.calendar-dates');

// News
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');

// Admin News Management
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/news', [App\Http\Controllers\Admin\NewsController::class, 'index'])->name('admin.news.index');
    Route::post('/admin/news/scrape', [App\Http\Controllers\Admin\NewsController::class, 'scrape'])->name('admin.news.scrape');
});

// Train routes
Route::get('/api/train/stops', [TrainController::class, 'getStops']);
Route::get('/api/train/search-route/{fromStop}/{toStop}', [TrainController::class, 'searchRoute']);
Route::get('/api/train/route/{routeId}/{tripId?}', [TrainController::class, 'getRouteDetails']);
Route::get('/api/train/stop-times/{stopId}', [TrainController::class, 'getStopTimes']);

// API Routes
Route::prefix('api')->group(function () {
    // ... existing routes ...

    // Get routes for a stop
    Route::get('/stop-routes/{stop_id}', function ($stop_id, Request $request) {
        $database = $request->query('database');

        if ($database) {
            $db = DB::connection($database);

            // Get all routes that pass through this stop
            $routes = $db->table('routes')
                ->join('trips', 'routes.route_id', '=', 'trips.route_id')
                ->join('stop_times', 'trips.trip_id', '=', 'stop_times.trip_id')
                ->where('stop_times.stop_id', $stop_id)
                ->select('routes.route_id', 'routes.route_short_name', 'routes.route_long_name', 'routes.route_color')
                ->distinct()
                ->get();
        } else {
            // Use default database (Riga)
            $routes = DB::table('routes')
                ->join('trips', 'routes.route_id', '=', 'trips.route_id')
                ->join('stop_times', 'trips.trip_id', '=', 'stop_times.trip_id')
                ->where('stop_times.stop_id', $stop_id)
                ->select('routes.route_id', 'routes.route_short_name', 'routes.route_long_name', 'routes.route_color')
                ->distinct()
                ->get();
        }

        return response()->json($routes);
    });

    // Get route_id for a trip
    Route::get('/trip/{trip_id}/route', function ($trip_id, Request $request) {
        $database = $request->query('database');

        if ($database) {
            $db = DB::connection($database);
            $trip = $db->table('trips')
                ->where('trip_id', $trip_id)
                ->first(['route_id']);
        } else {
            $trip = DB::table('trips')
                ->where('trip_id', $trip_id)
                ->first(['route_id']);
        }

        if (!$trip) {
            return response()->json(['error' => 'Trip not found'], 404);
        }

        return response()->json(['route_id' => $trip->route_id]);
    });

    // ... existing routes ...
});
