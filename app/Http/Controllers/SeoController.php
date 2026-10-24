<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\Http\Response;

/**
 * Dynamic sitemap.xml and robots.txt. Small, fast, and never lists pages the
 * public cannot see: admin areas and previews stay out, kecamatan pages join
 * only when they have published content so thin URL farms stay away.
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $entries = [];

        $entries[] = ['loc' => route('home'), 'priority' => '1.0'];
        $entries[] = ['loc' => route('catalog.index', ['category' => 'pelatihan']), 'priority' => '0.9'];
        $entries[] = ['loc' => route('catalog.index', ['category' => 'jasa']), 'priority' => '0.9'];

        $itemsByCategory = CatalogItem::query()
            ->orderBy('category')
            ->orderBy('source_order')
            ->get(['slug', 'category']);

        foreach ($itemsByCategory as $item) {
            $entries[] = ['loc' => route('catalog.show', ['category' => $item->category, 'item' => $item->slug]), 'priority' => '0.8'];
        }

        $articlesForSitemap = Article::query()->published()->latest('published_at')->get(['slug', 'updated_at']);
        foreach ($articlesForSitemap as $article) {
            $entries[] = ['loc' => route('articles.show', $article), 'priority' => '0.7', 'lastmod' => $article->updated_at->toAtomString()];
        }

        foreach (City::query()->orderBy('id')->get(['slug']) as $city) {
            $entries[] = ['loc' => route('regional.city', $city), 'priority' => '0.6'];
        }

        $kecamatans = Kecamatan::query()->with('city:id,slug')->withCount(['articles' => fn ($q) => $q->published()])->orderBy('id')->get();

        foreach ($kecamatans as $kecamatan) {
            if ((int) $kecamatan->articles_count > 0) {
                $entries[] = ['loc' => route('regional.kecamatan', [$kecamatan->city, $kecamatan]), 'priority' => '0.5'];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <url>'."\n"
                .'    <loc>'.htmlspecialchars((string) $entry['loc'], ENT_XML1).'</loc>'."\n"
                .(isset($entry['lastmod']) ? '    <lastmod>'.($entry['lastmod']).'</lastmod>'."\n" : '')
                .'    <priority>'.($entry['priority']).'</priority>'."\n"
                .'  </url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /preview',
            'Disallow: /*?page=',

            '',
            // url() mirrors the public request scheme/host so the sitemap link
            // stays consistent with the canonical URLs generated here.
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
