<?php

namespace App\Providers;

use App\Models\CatalogItem;
use App\Models\City;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared operational counters for the public shell (top bar + footer):
        // every page shows the same live numbers without each controller
        // repeating the queries.
        View::composer('layouts.app', function ($view): void {
            $view->with([
                'countPelatihan' => CatalogItem::query()->category(CatalogItem::CATEGORY_PELATIHAN)->count(),
                'countJasa' => CatalogItem::query()->category(CatalogItem::CATEGORY_JASA)->count(),
                'countCities' => City::query()->count(),
                'hotline' => WhatsApp::random(),
            ]);
        });
    }
}
