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


Route::get('/', function () {
    return Inertia::render('home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');
Route::get('/bus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%bus%')->get();
    return Inertia::render('bus', [
        'routes' => $routes,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/trolleybus', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%trol%')->get();
    return Inertia::render('trolleybus', [
        'canLogin' => Route::has('login'),
        'routes' => $routes,
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/tram', function () {
    $routes = ModelRoute::where('route_id', 'LIKE', '%tram%')->get();
    return Inertia::render('tram', [
        'canLogin' => Route::has('login'),
        'routes' => $routes,
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/train', function () {
    return Inertia::render('train', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/login', function () {
    return Inertia::render('login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/test', function () {
    return Inertia::render('Test', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/route/details/{route_id}', function ($route_id) {
    $route = ModelRoute::findOrFail($route_id);
    $stops = $route->stops;  // Ensure you have a 'stops' relationship on your Route model

    return Inertia::render('RouteDetails', [
        'route' => $route,
        'stops' => $stops
    ]);
})->name('route.details');






Route::post('/import/{type}', [GraphicController::class, 'importExcelData']);



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/register', [RegisteredUserController::class, 'create'])
    ->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
    ->name('register.store');
});

Route::post('/import-shapes', [GraphicController::class, 'importShapes'])->name('import.shapes');
Route::post('/import-calendar', [GraphicController::class, 'importCalendar'])->name('import.calendar');
Route::post('/import-stops', [GraphicController::class, 'importStops'])->name('import.stops');
Route::post('/import-routes', [GraphicController::class, 'importRoutes'])->name('import.routes');
Route::post('/import-calendar-dates', [GraphicController::class, 'importCalendarDates'])->name('import.calendar_dates');
Route::post('/import-trips', [GraphicController::class, 'importTrips'])->name('import.trips');
Route::post('/import-stop-times', [GraphicController::class, 'importStopTimes'])->name('import.stop_times');

require __DIR__.'/auth.php';

Route::post('/search-route', [RouteController::class, 'searchRoute'])->name('searchRoute');
Route::get('/route/details/{route_id}', function ($route_id) {
    $route = ModelRoute::findOrFail($route_id);
    return Inertia::render('RouteDetails', [
        'route' => $route,
    ]);
})->name('route.details');
Route::get('/route/{id}', [RouteController::class, 'show'])->name('route.show');
