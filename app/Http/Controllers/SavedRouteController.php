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
            'schedule_types' => 'array|nullable', // Make schedule_types optional
            'schedule_types.*' => 'string|nullable',    // Each schedule type should be a string
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

        // 3. Assign the array of times with their schedule types
        if (isset($validatedData['schedule_types'])) {
            $savedRecord->saved_times = array_map(function($time, $scheduleType) {
                return [
                    'time' => $time,
                    'schedule_type' => $scheduleType
                ];
            }, $validatedData['times'], $validatedData['schedule_types']);
        } else {
            // If no schedule types provided, just save the times
            $savedRecord->saved_times = array_map(function($time) {
                return [
                    'time' => $time,
                    'schedule_type' => 'workday' // Default to workday
                ];
            }, $validatedData['times']);
        }

        // 4. Save the record to the database.
        $savedRecord->save();

        return response()->json(['status' => 'success', 'message' => 'Stop times saved successfully.']);
    }

    /**
     * Display a listing of the user's saved stop times.
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Fetch saved times with trip and stop information
        $query = Auth::user()->savedStopTimes()
            ->select('user_saved_stop_times.*')
            ->join('trips', 'user_saved_stop_times.trip_id', '=', 'trips.trip_id')
            ->join('routes', 'trips.route_id', '=', 'routes.route_id')
            ->join('stops', 'user_saved_stop_times.stop_id', '=', 'stops.stop_id')
            ->leftJoin('calendar', 'trips.service_id', '=', 'calendar.service_id')
            ->addSelect([
                'routes.route_id',
                'routes.route_short_name',
                'routes.route_long_name',
                'routes.route_color',
                'stops.stop_name',
                'calendar.monday',
                'calendar.tuesday',
                'calendar.wednesday',
                'calendar.thursday',
                'calendar.friday',
                'calendar.saturday',
                'calendar.sunday',
                'trips.service_id'  // Add service_id for debugging
            ]);

        if ($request->has('trip_id')) {
            $query->where('user_saved_stop_times.trip_id', $request->input('trip_id'));
        }
        if ($request->has('stop_id')) {
            $query->where('user_saved_stop_times.stop_id', $request->input('stop_id'));
        }

        $results = $query->get()->map(function ($item) {
            // Default to both workday and weekend if no calendar data
            $isWorkday = true;
            $isWeekend = true;

            // Only override if we have calendar data
            if (!is_null($item->monday)) {
                $isWorkday = $item->monday == 1 || $item->tuesday == 1 ||
                            $item->wednesday == 1 || $item->thursday == 1 ||
                            $item->friday == 1;
                $isWeekend = $item->saturday == 1 || $item->sunday == 1;
            }

            $schedule = [];
            if ($isWorkday) $schedule[] = 'workday';
            if ($isWeekend) $schedule[] = 'weekend';

            // If no schedule type is set, default to both
            if (empty($schedule)) {
                $schedule = ['workday', 'weekend'];
            }

            return [
                'id' => $item->id,
                'trip_id' => $item->trip_id,
                'stop_id' => $item->stop_id,
                'route_id' => $item->route_id,
                'route_name' => $item->route_long_name ?? $item->route_short_name,
                'stop_name' => $item->stop_name,
                'saved_times' => $item->saved_times,
                'route_color' => $item->route_color,
                'schedule_type' => $schedule,
                'service_id' => $item->service_id  // Include service_id for debugging
            ];
        });

        return $results;
    }

    public function destroy(string $id) // Ensure the parameter matches the route segment
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Split the ID to get the record ID and the time to remove
        $parts = explode('-', $id);
        $recordId = $parts[0];
        $timeToRemove = $parts[1] ?? null;

        $savedRecord = UserSavedStopTime::where('id', $recordId)
                                        ->where('user_id', Auth::id()) // Ensure user owns the record
                                        ->first();

        if (!$savedRecord) {
            return response()->json(['message' => 'Saved record not found or unauthorized.'], 404);
        }

        if ($timeToRemove) {
            // Remove only the specific time from the saved_times array
            $savedTimes = collect($savedRecord->saved_times);
            $updatedTimes = $savedTimes->filter(function ($timeData) use ($timeToRemove) {
                $time = is_array($timeData) ? $timeData['time'] : $timeData;
                return $time !== $timeToRemove;
            })->values()->all();

            if (empty($updatedTimes)) {
                // If no times left, delete the entire record
                $savedRecord->delete();
            } else {
                // Update with remaining times
                $savedRecord->saved_times = $updatedTimes;
                $savedRecord->save();
            }
        } else {
            // If no specific time provided, delete the entire record
            $savedRecord->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Stop time deleted successfully.']);
    }

}
