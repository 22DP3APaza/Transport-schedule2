<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StopTimeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|integer',
            'stop_id' => 'required|integer',
            'times' => 'required|array',
            'times.*' => 'required|string', // Format like "12:00"
            'stop_sequence' => 'required|integer', // Add this line
        ]);

        try {
            $insertData = [];
            foreach ($validated['times'] as $time) {
                $insertData[] = [
                    'trip_id' => $validated['trip_id'],
                    'stop_id' => $validated['stop_id'],
                    'departure_time' => $time,
                    'arrival_time' => $time,
                    'stop_sequence' => $validated['stop_sequence'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('stop_times')->insert($insertData);

            return response()->json([
                'message' => 'Stop times saved successfully.',
                'saved_count' => count($insertData)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save stop times: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save stop times.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
