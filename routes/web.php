<?php

use App\Http\Controllers\GraphicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CSVImportController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Route as ModelRoute;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;


Route::get('/', function () {
    return Inertia::render('home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/bus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%bus%')->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('bus', compact('routes'));
});

Route::get('/trolleybus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%trol%')->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('trolleybus', compact('routes'));
});

Route::get('/tram', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%tram%')->get(['route_id', 'route_short_name', 'route_long_name', 'route_color']);
    return Inertia::render('tram', compact('routes'));
});

Route::get('/train', function () {
    return Inertia::render('train');
});

Route::get('/login', function () {
    return Inertia::render('login');
});

Route::get('/test', function () {
    return Inertia::render('Test');
});



Route::get('/route/details/{route_id}/{trip_id?}', function ($route_id, $trip_id = null) {
    // Fetch the route details
    $route = ModelRoute::where('route_id', $route_id)->firstOrFail([
        'route_id', 'route_short_name', 'route_long_name', 'route_color'
    ]);

    // Fetch all trips for the route
    $trips = Trip::where('route_id', $route_id)->get();

    // Group trips by both trip_headsign and shape_id to ensure uniqueness
    $groupedTrips = $trips->groupBy(function ($trip) {
        return $trip->trip_headsign . '|' . $trip->shape_id;
    })->map(function ($group) {
        return $group->first(); // Take the first trip in each group
    });

    // Determine the selected trip
    $trip = $trip_id ? $trips->where('trip_id', $trip_id)->first() : $groupedTrips->first();

    // Fetch stops for the selected trip
    $stops = $trip ? Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip->trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.*', 'stop_times.stop_sequence']) : [];

    // Render the Inertia view with the data
    return Inertia::render('RouteDetails', [
        'route' => $route,
        'trips' => $groupedTrips->values(), // Pass grouped trips to the frontend
        'selectedTrip' => $trip,
        'stops' => $stops,
    ]);
})->name('route.details');

Route::get('/route/details/{route_id}/{trip_id}/stops', function ($route_id, $trip_id) {
    $stops = Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.*', 'stop_times.arrival_time', 'stop_times.departure_time']);

    return response()->json($stops);
})->name('route.trip.stops');

Route::get('/stop/times/{stop_id}', function ($stop_id) {
    $type = request('type', 'workdays'); // Default to workdays

    $query = DB::table('calendar');

    if ($type === 'workdays') {
        $query->where('monday', 1)
              ->where('tuesday', 1)
              ->where('wednesday', 1)
              ->where('thursday', 1)
              ->where('friday', 1);
    } else {
        $query->where('saturday', 1)
              ->orWhere('sunday', 1);
    }

    $serviceIds = $query->pluck('service_id');

    $stopTimes = StopTime::where('stop_id', $stop_id)
        ->whereIn('trip_id', function ($subQuery) use ($serviceIds) {
            $subQuery->select('trip_id')
                ->from('trips')
                ->whereIn('service_id', $serviceIds);
        })
        ->orderBy('arrival_time')
        ->get(['trip_id', 'arrival_time', 'departure_time', 'stop_sequence']);

    return response()->json($stopTimes);
})->name('stop.times');

// Import routes
Route::post('/import/{type}', [GraphicController::class, 'importExcelData']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

// Importing GTFS data
Route::post('/import-shapes', [GraphicController::class, 'importShapes'])->name('import.shapes');
Route::post('/import-calendar', [GraphicController::class, 'importCalendar'])->name('import.calendar');
Route::post('/import-stops', [GraphicController::class, 'importStops'])->name('import.stops');
Route::post('/import-routes', [GraphicController::class, 'importRoutes'])->name('import.routes');
Route::post('/import-calendar-dates', [GraphicController::class, 'importCalendarDates'])->name('import.calendar_dates');
Route::post('/import-trips', [GraphicController::class, 'importTrips'])->name('import.trips');
Route::post('/import-stop-times', [GraphicController::class, 'importStopTimes'])->name('import.stop_times');

require __DIR__.'/auth.php';

Route::post('/search-route', [RouteController::class, 'searchRoute'])->name('searchRoute');
Route::get('/route/{id}', [RouteController::class, 'show'])->name('route.show');
