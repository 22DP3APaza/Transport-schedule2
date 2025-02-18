<?php
namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteController extends Controller
{
    // Existing methods...

    public function searchRoute(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type'); // Get the type parameter (e.g., 'bus')

        // Construct the route long name
        $routeLongName = "{$from} - {$to}";

        // Search for the route in the database, filtering by type
        $route = Route::where('route_long_name', $routeLongName)
                      ->where('route_id', 'LIKE', "%{$type}%") // Filter by type (e.g., 'bus')
                      ->first();

        if ($route) {
            // Redirect to the specific route page using Inertia
            return Inertia::location(route('route.details', ['route_id' => $route->route_id]));
        }

        // If no match found, return an error message with Inertia
        return back()->withErrors(['error' => 'Route not found.']);
    }
}
