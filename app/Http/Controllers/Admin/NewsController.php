<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Artisan;

class NewsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/News', [
            'totalNews' => News::count(),
            'isLoading' => false,
        ]);
    }

    public function scrape(Request $request)
    {
        $command = $request->input('command');

        if (!in_array($command, ['news:scrape', 'news:scrape-first'])) {
            return response()->json(['error' => 'Invalid command'], 400);
        }

        try {
            Artisan::call($command);
            return response()->json([
                'success' => true,
                'totalNews' => News::count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
