<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\Catalog\CatalogManifest;
use App\Services\Catalog\Content\FieldBlueprint;
use App\Services\Catalog\RelatedItems;
use App\Support\WhatsApp;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(string $category): View
    {
        abort_unless(in_array($category, CatalogManifest::CATEGORIES, true), 404);

        $items = CatalogItem::query()
            ->category($category)
            ->when(request('q'), fn ($query, string $term) => $query->search($term))
            ->orderBy('source_order')
            ->withCount('articles')
            ->get();

        return view('catalog.index', [
            'category' => $category,
            'items' => $items,
            'callout' => WhatsApp::callout(),
        ]);
    }

    public function show(string $category, string $itemSlug): View
    {
        return $this->regionalShow($category, $itemSlug);
    }

    // City-specific catalog index
    public function cityIndex(City $city, string $category): View
    {
        abort_unless(in_array($category, CatalogManifest::CATEGORIES, true), 404);

        $items = CatalogItem::query()
            ->category($category)
            ->when(request('q'), fn ($query, string $term) => $query->search($term))
            ->orderBy('source_order')
            ->withCount('articles')
            ->get();

        return view('catalog.index', [
            'category' => $category,
            'items' => $items,
            'city' => $city,
            'callout' => WhatsApp::callout(),
        ]);
    }

    // City-specific catalog show
    public function cityShow(City $city, string $category, string $itemSlug): View
    {
        return $this->regionalShow($category, $itemSlug, $city);
    }

    // Kecamatan-specific catalog index
    public function kecamatanIndex(City $city, string $kecamatanSlug, string $category): View
    {
        abort_unless(in_array($category, CatalogManifest::CATEGORIES, true), 404);

        $kecamatan = Kecamatan::query()->where('city_id', $city->id)->where('slug', $kecamatanSlug)->firstOrFail();

        return view('catalog.index', [
            'category' => $category,
            'items' => CatalogItem::query()->category($category)
                ->when(request('q'), fn ($query, string $term) => $query->search($term))
                ->orderBy('source_order')->withCount('articles')->get(),
            'city' => $city,
            'kecamatan' => $kecamatan,
            'callout' => WhatsApp::callout(),
        ]);
    }

    /**
     * Kecamatan-specific catalog show. Kecamatan slugs are only unique per city,
     * so lookups are always scoped to the bound city; anything else is a 404.
     */
    public function kecamatanShow(City $city, string $kecamatanSlug, string $category, string $itemSlug): View
    {
        $kecamatan = Kecamatan::query()
            ->where('city_id', $city->id)
            ->where('slug', $kecamatanSlug)
            ->firstOrFail();

        return $this->regionalShow($category, $itemSlug, $kecamatan->city, $kecamatan);
    }

    /**
     * Detail renderer shared by generic, city-contextual and kecamatan-contextual
     * routes. $kecamatan is non-null only for kecamatan context (whose city is
     * its parent). The master catalog content is shown unchanged; the regional
     * context only shapes breadcrumbs, intro, CTA wording and catalog links.
     */
    /**
     * Detail renderer shared by generic, city-contextual and kecamatan-contextual
     * routes. $kecamatan is non-null only for kecamatan context ($city then
     * carries its parent city). The master catalog content is shown unchanged;
     * the regional context only shapes breadcrumbs, intro, CTA wording and
     * catalog links.
     */
    private function regionalShow(string $category, string $itemSlug, ?City $city = null, ?Kecamatan $kecamatan = null): View
    {
        abort_unless(in_array($category, CatalogManifest::CATEGORIES, true), 404);

        $item = CatalogItem::query()
            ->where('slug', $itemSlug)
            ->where('category', $category)
            ->firstOrFail();

        $articles = $item->articles()
            ->published()
            ->latest('published_at')
            ->take(5)
            ->get();

        $cities = City::query()
            ->orderBy('province')
            ->orderBy('name')
            ->get(['name', 'slug', 'province', 'classification']);

        return view('catalog.show', [
            'item' => $item,
            'content' => $item->content,
            'articles' => $articles,
            'relatedItems' => (new RelatedItems)->for($item),
            'fields' => FieldBlueprint::definitions($category),
            'callout' => WhatsApp::callout(),
            'cities' => $cities,
            'city' => $kecamatan?->city ?? $city,
            'kecamatan' => $kecamatan,
        ]);
    }
}
