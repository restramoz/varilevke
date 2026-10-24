<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\City;
use App\Models\Kecamatan;
use App\Support\WhatsApp;
use Illuminate\View\View;

class RegionalController extends Controller
{
    public function cities(): View
    {
        return view('regional.cities', [
            'cities' => City::query()
                ->orderBy('province')
                ->orderBy('classification')
                ->orderBy('name')
                ->withCount(['articles' => fn ($q) => $q->published()])
                ->get()
                ->groupBy('province'),
            'callout' => WhatsApp::callout(),
        ]);
    }

    public function city(City $city): View
    {
        return view('regional.city', [
            'city' => $city->loadCount(['kecamatans', 'articles' => fn ($q) => $q->published()]),
            'articles' => Article::query()->published()->where('city_id', $city->id)
                ->latest('published_at')->take(6)->get(),
            'kecamatans' => Kecamatan::query()->where('city_id', $city->id)
                ->withCount(['articles' => fn ($q) => $q->published()])
                ->orderBy('name')->get(),
            'callout' => WhatsApp::callout(),
        ]);
    }

    /**
     * Kecamatan slug is only unique per city, so binding is explicit.
     */
    public function kecamatan(City $city, string $kecamatanSlug): View
    {
        $kecamatan = Kecamatan::query()
            ->where('city_id', $city->id)
            ->where('slug', $kecamatanSlug)
            ->firstOrFail();

        return view('regional.kecamatan', [
            'kecamatan' => $kecamatan->load('city'),
            'articles' => Article::query()->published()->where('kecamatan_id', $kecamatan->id)
                ->latest('published_at')->take(6)->get(),
            'callout' => WhatsApp::callout(),
        ]);
    }
}
