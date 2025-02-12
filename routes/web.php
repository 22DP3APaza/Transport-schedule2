<?php
use App\Http\Controllers\GraphicController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CSVImportController;

Route::get('/', function () {
    return Inertia::render('home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');
Route::get('/buss', function () {
    return Inertia::render('buss', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/trolleybuss', function () {
    return Inertia::render('trolleybuss', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/tram', function () {
    return Inertia::render('tram', [
        'canLogin' => Route::has('login'),
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



Route::post('/import/{type}', [GraphicController::class, 'importExcelData']);



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/import-shapes', [GraphicController::class, 'importShapes'])->name('import.shapes');
Route::post('/import-calendar', [GraphicController::class, 'importCalendar'])->name('import.calendar');
Route::post('/import-stops', [GraphicController::class, 'importStops'])->name('import.stops');
Route::post('/import-routes', [GraphicController::class, 'importRoutes'])->name('import.routes');
Route::post('/import-calendar-dates', [GraphicController::class, 'importCalendarDates'])->name('import.calendar_dates');
Route::post('/import-trips', [GraphicController::class, 'importTrips'])->name('import.trips');
Route::post('/import-stop-times', [GraphicController::class, 'importStopTimes'])->name('import.stop_times');

require __DIR__.'/auth.php';
