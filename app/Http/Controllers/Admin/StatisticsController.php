<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Route;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index()
    {
        $statistics = [
            'total_users' => User::count(),
            'total_routes' => Route::count(),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_routes' => Route::take(5)
                ->get(['route_id', 'route_short_name', 'route_long_name']),
        ];

        return Inertia::render('Admin/Statistics/Index', [
            'statistics' => $statistics
        ]);
    }
}
