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
    $route = ModelRoute::where('route_id', $route_id)->firstOrFail(['route_id', 'route_short_name', 'route_long_name', 'route_color']);

    $trips = Trip::where('route_id', $route_id)->get();

    // Group trips by trip_headsign
    $groupedTrips = $trips->groupBy('trip_headsign')->map(function ($group) {
        return $group->first(); // Take the first trip in each group
    });

    $trip = $trip_id ? $trips->where('trip_id', $trip_id)->first() : $groupedTrips->first();

    $stops = $trip ? Stop::join('stop_times', 'stops.stop_id', '=', 'stop_times.stop_id')
        ->where('stop_times.trip_id', $trip->trip_id)
        ->orderBy('stop_times.stop_sequence')
        ->get(['stops.*', 'stop_times.stop_sequence']) : [];

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
