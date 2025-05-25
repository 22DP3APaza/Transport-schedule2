<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSavedStopTime;
use Illuminate\Support\Facades\Auth;

class SavedRouteController extends Controller
{
    /**
     * Store or update a user's saved stop times for a specific trip and stop.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming request data
        $validatedData = $request->validate([
            'trip_id' => 'required|string',
            'stop_id' => 'required|string',
            'times' => 'required|array',      // 'times' should be an array
            'times.*' => 'string',            // Each item in the 'times' array should be a string
        ]);

        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userId = Auth::id();

        // 2. Find an existing record for this user, trip, and stop, or create a new instance.
        $savedRecord = UserSavedStopTime::firstOrNew([
            'user_id' => $userId,
            'trip_id' => $validatedData['trip_id'],
            'stop_id' => $validatedData['stop_id'],
        ]);

        // 3. Assign the array of times.
        $savedRecord->saved_times = $validatedData['times'];

        // 4. Save the record to the database.
        $savedRecord->save();

        return response()->json(['status' => 'success', 'message' => 'Stop times saved successfully.']);
    }

    /**
     * Display a listing of the user's saved stop times.
     */
    public function index(Request $request) // Add Request $request to get query params
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Fetch saved times for specific trip_id and stop_id if provided
        $query = Auth::user()->savedStopTimes();

        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }
        if ($request->has('stop_id')) {
            $query->where('stop_id', $request->input('stop_id'));
        }

        return $query->get();
    }

    public function destroy(string $id) // Ensure the parameter matches the route segment
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $savedRecord = UserSavedStopTime::where('id', $id)
                                        ->where('user_id', Auth::id()) // Ensure user owns the record
                                        ->first();

        if (!$savedRecord) {
            return response()->json(['message' => 'Saved record not found or unauthorized.'], 404);
        }

        $savedRecord->delete();

        return response()->json(['status' => 'success', 'message' => 'Stop time deleted successfully.']);
    }

}
