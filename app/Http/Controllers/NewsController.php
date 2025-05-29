<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $news = News::orderBy('date', 'desc')
            ->paginate(10);

        return Inertia::render('News/Index', [
            'news' => $news
        ]);
    }

    public function show($id)
    {
        $newsItem = News::findOrFail($id);

        return Inertia::render('News/Show', [
            'newsItem' => $newsItem
        ]);
    }
}
