<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Support\WhatsApp;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $pelatihanCount = CatalogItem::query()->category(CatalogItem::CATEGORY_PELATIHAN)->count();
        $jasaCount = CatalogItem::query()->category(CatalogItem::CATEGORY_JASA)->count();
        $cityCount = City::query()->count();
        $cities = City::query()->orderBy('name')->limit(40)->get();

        return view('home', [
            'latestArticles' => Article::query()->published()->latest('published_at')->take(4)->get(),
            'catalogOverview' => [
                'pelatihan_count' => $pelatihanCount,
                'jasa_count' => $jasaCount,
            ],
            'pillars' => [
                'pelatihan' => $this->pillar(
                    'pelatihan',
                    $pelatihanCount,
                    'Pelatihan & Kompetensi K3',
                    'Program kompetensi untuk personel keselamatan kerja, operasi alat berat, migas, kelistrikan, hingga higiene industri.',
                    'badge-accent',
                ),
                'jasa' => $this->pillar(
                    'jasa',
                    $jasaCount,
                    'Jasa Teknis, Lingkungan & Administrasi',
                    'Perizinan, kajian risiko, publikasi ilmiah, dan layanan administrasi perusahaan — dikurasi per dokumen sumber.',
                    'badge-caution',
                ),
            ],
            'cityOptions' => $cities->map(fn (City $city): array => [
                'label' => $city->label(),
                'url' => route('regional.city', $city),
            ])->all(),
            'callout' => WhatsApp::callout(),
            'countPelatihan' => $pelatihanCount,
            'countJasa' => $jasaCount,
            'countCities' => $cityCount,
            'hotline' => WhatsApp::random(),
        ]);
    }

    private function counts(string $category): int
    {
        return CatalogItem::query()->category($category)->count();
    }

    /** @return mixed */
    private function pillar(string $category, int $count, string $title, string $desc, string $badge)
    {
        return [
            'category' => $category,
            'count' => $count,
            'title' => $title,
            'desc' => $desc,
            'badge' => $badge,
            'items' => CatalogItem::query()->category($category)->orderBy('source_order')->limit(5)->get(),
        ];
    }
}
