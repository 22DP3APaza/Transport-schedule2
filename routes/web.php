<?php

use App\Http\Controllers\GraphicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
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
    })->filter(); // Remove null entries

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
Route::get('/stop/times/{stop_id}', function (Request $request, $stop_id) {
    Log::info('Request received for stop_id: ' . $stop_id . ', route_id: ' . $request->query('route_id') . ', type: ' . $request->query('type', 'workdays'));

    $type = $request->query('type', 'workdays');
    $route_id = $request->query('route_id');
    $trip_id = $request->query('trip_id');

    if (!$route_id || !$trip_id) {
        Log::error('Route ID or Trip ID is missing');
        return response()->json(['error' => 'Route ID and Trip ID are required'], 400);
    }

    // Get the reference trip's stops sequence
    $referenceStops = DB::table('stop_times')
        ->where('trip_id', $trip_id)
        ->orderBy('stop_sequence')
        ->pluck('stop_id', 'stop_sequence');

    if ($referenceStops->isEmpty()) {
        return response()->json([]);
    }

    // === Determine active service IDs for the route ===
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

    $activeServiceIds = $calendarQuery->pluck('service_id');
    $today = now()->format('Ymd');

    $addedServices = DB::table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 1)
        ->pluck('service_id');

    $removedServices = DB::table('calendar_dates')
        ->where('date', $today)
        ->where('exception_type', 2)
        ->pluck('service_id');

    $finalServiceIds = $activeServiceIds->merge($addedServices)->unique()->diff($removedServices);

    if ($finalServiceIds->isEmpty()) {
        return response()->json([]);
    }

    // Get all trips for this route with active services
    $potentialTrips = DB::table('trips')
        ->where('route_id', $route_id)
        ->whereIn('service_id', $finalServiceIds)
        ->pluck('trip_id');

    // Filter trips to only those that have exactly the same stops in the same sequence
    $matchingTripIds = collect();
    foreach ($potentialTrips as $potentialTripId) {
        $tripStops = DB::table('stop_times')
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
    $stopTimes = DB::table('stop_times')
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

    Log::info('Request received for /stoptimes. Specific Trip ID: ' . $trip_id . ', Stop ID: ' . $stop_id . ', Departure Time: ' . $departure_time);

    if (!$trip_id || !$stop_id || !$departure_time) {
        Log::error('Missing parameters for /stoptimes request. Required: trip_id, stop_id, departure_time.');
        return response()->json(['error' => 'Trip ID, Stop ID and Departure Time are required'], 400);
    }

    // Fetch the route_id associated with the provided trip_id
    $tripDetails = DB::table('trips')
        ->where('trip_id', $trip_id)
        ->first(['route_id', 'shape_id']);

    if (!$tripDetails) {
        Log::warning('Trip not found for provided trip ID: ' . $trip_id);
        return response()->json([]);
    }

    $foundRouteId = $tripDetails->route_id;
    $foundShapeId = $tripDetails->shape_id;

    //fetches all stop times for this specific provided trip_id
    $stopTimes = DB::table('stop_times')
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

    // Get all GTFS stops
    $allStops = Stop::orderBy('stop_name')->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);

    return Inertia::render('RouteMap', [
        'route' => $route,
        'trip' => $trip,
        'shapePoints' => $shapePoints,
        'stops' => $stops,
        'allStops' => $allStops //  Pass to Vue
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

Route::get('/api/stops', function () {
    $stops = Stop::select('stop_id', 'stop_name')
        ->orderBy('stop_name')
        ->get();

    return response()->json($stops);
});


Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::put('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggleAdmin');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
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
Route::get('/api/possible-destinations', [RouteController::class, 'getPossibleDestinations'])->name('api.possible-destinations');
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
