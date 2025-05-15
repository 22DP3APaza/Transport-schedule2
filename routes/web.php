<?php

use App\Http\Controllers\GraphicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CSVImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\GtfsStopController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Route as ModelRoute;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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

// Route: Train (stub)
Route::get('/train', function () {
    return Inertia::render('train');
});

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
Route::get('/route/details/{route_id}/{trip_id?}', function ($route_id, $trip_id = null) {
    // Fetch the route details
    $route = ModelRoute::where('route_id', $route_id)->firstOrFail([
        'route_id', 'route_short_name', 'route_long_name', 'route_color'
    ]);

    // Fetch all trips for the route
    $trips = Trip::where('route_id', $route_id)->get();

    // Enhance each trip with first and last stop names
    $enhancedTrips = $trips->map(function ($trip) {
        // Get first stop
        $firstStop = DB::table('stop_times')
            ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
            ->where('stop_times.trip_id', $trip->trip_id)
            ->orderBy('stop_times.stop_sequence')
            ->value('stops.stop_name');

        // Get last stop
        $lastStop = DB::table('stop_times')
            ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
            ->where('stop_times.trip_id', $trip->trip_id)
            ->orderByDesc('stop_times.stop_sequence')
            ->value('stops.stop_name');

        // Set a clean trip name (first → last stop)
        $trip->full_name = $firstStop . ' → ' . $lastStop;
        return $trip;
    });

    // Group trips by trip_headsign and full_name (to capture direction)
    $groupedTrips = $enhancedTrips->groupBy(function ($trip) {
        return $trip->trip_headsign . '|' . $trip->full_name;
    })->map(function ($group) {
        return $group->first(); // Take the first trip in each group
    });

    // Determine the selected trip
    $trip = $trip_id ? $enhancedTrips->where('trip_id', $trip_id)->first() : $groupedTrips->first();

    // Fetch stops for the selected trip
    $stops = $trip ? Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip->trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.*', 'stop_times.stop_sequence']) : [];

    // Render the Inertia view with the data
    return Inertia::render('RouteDetails', [
        'route' => $route,
        'trips' => $groupedTrips->values(),
        'selectedTrip' => $trip,
        'stops' => $stops,
    ]);
})->name('route.details');

// Get Stops for a Specific Trip
Route::get('/route/details/{route_id}/{trip_id}/stops', function ($route_id, $trip_id) {
    $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.*', 'stop_times.arrival_time', 'stop_times.departure_time']);

    return response()->json($stops);
})->name('route.trip.stops');

// Get Stop Times for a Specific Stop and Route
Route::get('/stop/times/{stop_id}', function ($stop_id) {
    $type = request('type', 'workdays'); // Default to workdays
    $route_id = request('route_id'); // Required

    if (!$route_id) {
        return response()->json(['error' => 'Route ID is required'], 400);
    }

    $today = now()->format('Ymd');

    // === Determine service IDs ===
    $calendarQuery = DB::table('calendar');

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

    $baseServiceIds = $calendarQuery->pluck('service_id');
    $addedServices = DB::table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 1)
        ->pluck('service_id');
    $removedServices = DB::table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 2)
        ->pluck('service_id');

    $serviceIds = $baseServiceIds->merge($addedServices)->unique()->diff($removedServices);

    if ($serviceIds->isEmpty()) {
        return response()->json(['error' => 'No active services found for this day type'], 404);
    }

    // === Get shape_ids used by this route ===
    $shapeIds = DB::table('trips')
        ->where('route_id', $route_id)
        ->whereIn('service_id', $serviceIds)
        ->pluck('shape_id')
        ->unique();

    if ($shapeIds->isEmpty()) {
        return response()->json(['error' => 'No shape_ids found for this route'], 404);
    }

    // === Get trip_ids using these shape_ids and services ===
    $tripIds = DB::table('trips')
        ->whereIn('shape_id', $shapeIds)
        ->whereIn('service_id', $serviceIds)
        ->pluck('trip_id');

    if ($tripIds->isEmpty()) {
        return response()->json(['error' => 'No trips found for route/shape/service combination'], 404);
    }

    // === Filter stop_times for this stop and those trip_ids ===
    $stopTimes = DB::table('stop_times')
        ->whereIn('trip_id', $tripIds)
        ->where('stop_id', $stop_id)
        ->orderBy('departure_time')
        ->get(['departure_time'])
        ->map(function ($item) {
            // Normalize 24:xx:xx to 00:xx:xx
            if (str_starts_with($item->departure_time, '24:')) {
                $item->departure_time = preg_replace('/^24:/', '00:', $item->departure_time);
                $item->was_24 = true; // mark so we can move it to the front
            } else {
                $item->was_24 = false;
            }
            return $item;
        })
        ->unique('departure_time')
        ->sortBy(function ($item) {
            return $item->departure_time;
        })
        ->sortByDesc('was_24') // put normalized 24:xx:xx (now 00:xx:xx) at the front
        ->values()
        ->map(function ($item) {
            // Return only the departure_time field
            return ['departure_time' => $item->departure_time];
        });

    if ($stopTimes->isEmpty()) {
        return response()->json(['error' => 'No stop times found for this stop and route'], 404);
    }

    return response()->json($stopTimes);
})->name('stop.times');

// Get Stop Times by Departure Time
Route::get('/stoptimes', function () {
    $trip_id = request('trip_id');
    $stop_id = request('stop_id');
    $departure_time = request('departure_time'); // Format: "HH:MM" or "H:MM"

    if (!$trip_id || !$stop_id || !$departure_time) {
        return response()->json(['error' => 'Trip ID, Stop ID and Departure Time are required'], 400);
    }

    // Format the time for database comparison (convert "5:15" to "05:15:00")
    $formattedTime = \Carbon\Carbon::createFromFormat('G:i', $departure_time)->format('H:i:s');

    // Get route and shape info from the trip
    $tripInfo = DB::table('trips')
        ->where('trip_id', $trip_id)
        ->first(['route_id', 'shape_id']);

    if (!$tripInfo) {
        return response()->json(['error' => 'Trip not found'], 404);
    }

    // Get the stop sequence for this stop in the original trip
    $originalSequence = DB::table('stop_times')
        ->where('trip_id', $trip_id)
        ->where('stop_id', $stop_id)
        ->value('stop_sequence');

    if (!$originalSequence) {
        return response()->json(['error' => 'Stop not found in original trip'], 404);
    }

    // Get all trips with same shape and route that have this stop at the same sequence with matching departure time
    $matchingTripIds = DB::table('stop_times')
        ->where('stop_id', $stop_id)
        ->where('stop_sequence', $originalSequence)
        ->where('departure_time', $formattedTime)
        ->whereIn('trip_id', function($query) use ($tripInfo) {
            $query->select('trip_id')
                  ->from('trips')
                  ->where('shape_id', $tripInfo->shape_id)
                  ->where('route_id', $tripInfo->route_id);
        })
        ->pluck('trip_id');

    // Get all stop times for matching trips
    $stopTimes = DB::table('stop_times')
        ->join('stops', 'stop_times.stop_id', '=', 'stops.stop_id')
        ->whereIn('stop_times.trip_id', $matchingTripIds)
        ->orderBy('stop_times.trip_id')
        ->orderBy('stop_times.stop_sequence')
        ->get([
            'stops.stop_name',
            'stops.stop_id',
            'stop_times.arrival_time',
            'stop_times.departure_time',
            'stop_times.stop_sequence',
            'stop_times.trip_id'
        ]);

    // Group by trip_id
    $groupedByTrip = $stopTimes->groupBy('trip_id');

    return Inertia::render('StopTimes', [
        'trips' => $groupedByTrip,
        'initialTripId' => $trip_id,
        'routeId' => $tripInfo->route_id,
        'selectedStopId' => $stop_id,
        'selectedDepartureTime' => $departure_time
    ]);
})->name('stoptimes');




Route::get('/route/map/{route_id}/{trip_id}', function ($route_id, $trip_id) {
    // Get route info
    $route = ModelRoute::where('route_id', $route_id)->firstOrFail([
        'route_id', 'route_short_name', 'route_long_name', 'route_color'
    ]);

    // Get trip info to find the shape
    $trip = Trip::where('trip_id', $trip_id)->firstOrFail([
        'trip_id', 'shape_id', 'trip_headsign'
    ]);

    // Get all shape points for this route
    $shapePoints = DB::table('shapes')
        ->where('shape_id', $trip->shape_id)
        ->orderBy('shape_pt_sequence')
        ->get(['shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence']);

    // Get all stops with their coordinates for this trip
    $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.stop_id', 'stops.stop_name', 'stops.stop_lat', 'stops.stop_lon', 'stop_times.stop_sequence']);

    // ✅ Get all GTFS stops
    $allStops = Stop::orderBy('stop_name')->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);

    return Inertia::render('RouteMap', [
        'route' => $route,
        'trip' => $trip,
        'shapePoints' => $shapePoints,
        'stops' => $stops,
        'allStops' => $allStops // ✅ Pass to Vue
    ]);
})->name('route.map');


// Import routes
Route::post('/import/{type}', [GraphicController::class, 'importExcelData']);

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::get('/api/stops', function () {
    $stops = Stop::select('stop_id', 'stop_name')
                ->orderBy('stop_name')
                ->get();

    return response()->json($stops);
});


Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');

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
Route::post('/search-route', [RouteController::class, 'searchRoute'])->name('searchRoute');
Route::get('/route/{id}', [RouteController::class, 'show'])->name('route.show');
