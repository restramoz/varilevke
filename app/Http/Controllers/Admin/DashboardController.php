<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'catalog' => CatalogItem::count(),
                'articles' => Article::count(),
                'cities' => City::count(),
                'kecamatans' => Kecamatan::count(),
            ],
            'aiEnabled' => (bool) config('services.ai.enabled'),
            'reviewRequiredCount' => CatalogItem::query()->where('review_required', true)->count(),
            'draftCount' => Article::query()->where('status', 'draft')->count(),
        ]);
    }
}
