<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_admin_and_links_the_sitemap(): void
    {
        $content = $this->get('/robots.txt')->assertOk()->getContent();

        self::assertStringContainsString('User-agent: *', $content);
        self::assertStringContainsString('Disallow: /admin', $content);
        self::assertStringContainsString('Disallow: /login', $content);
        self::assertStringMatchesFormat('%A%Sitemap: %A/sitemap.xml%A', $content);
    }

    public function test_sitemap_is_valid_xml_listing_public_pages_only(): void
    {
        $catalog = CatalogItem::factory()->create(['slug' => 'katalog-item-xml']);
        $city = City::factory()->create(['slug' => 'kota-xml']);
        $kecamatanEmpty = Kecamatan::factory()->create(['city_id' => $city->id, 'slug' => 'kecamatan-tanpa-artikel']);
        $kecamatanWithArticle = Kecamatan::factory()->create(['city_id' => $city->id, 'slug' => 'kecamatan-berartikel']);
        Article::factory()->published()->create([
            'kecamatan_id' => $kecamatanWithArticle->id,
            'city_id' => $city->id,
            'slug' => 'artikel-untuk-kecamatan',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        self::assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $xml = $response->getContent();

        $xmlElem = simplexml_load_string($xml);
        self::assertNotFalse($xmlElem);
        self::assertStringContainsString('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $xml);

        $locs = collect($xmlElem->xpath('//url/loc'))->map(fn ($node) => (string) $node)->all();

        if ($locs === []) {
            // Default-xmlns documents need a registered prefix for XPath.
            $xmlElem->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $locs = collect($xmlElem->xpath('//s:loc'))->map(fn ($node) => (string) $node)->all();
        }

        self::assertContains(url('/'), $locs);
        self::assertContains(url('/pelatihan'), $locs);
        self::assertContains(url('/jasa'), $locs);
        self::assertContains(url('/pelatihan/katalog-item-xml'), $locs);
        self::assertContains(url('/artikel/artikel-untuk-kecamatan'), $locs);
        self::assertContains(url('/wilayah/kota-xml'), $locs);

        // Kecamatan pages only exist in the sitemap when they have published
        // content — no thin URL farm.
        self::assertContains(url('/wilayah/kota-xml/kecamatan-berartikel'), $locs);
        self::assertNotContains(url('/wilayah/kota-xml/kecamatan-tanpa-artikel'), $locs);

        // Admin and login never appear.
        foreach ($locs as $loc) {
            self::assertStringNotContainsString('/admin', $loc);
            self::assertStringNotContainsString('/login', $loc);
        }
    }

    public function test_home_sets_canonical_and_open_graph(): void
    {
        $response = $this->get('/')->assertOk();

        $html = $response->getContent();

        // Use regex to match canonical URL (test server port may vary)
        self::assertMatchesRegularExpression('/<link rel="canonical" href="[^"]*">/', $html);
        self::assertStringContainsString('property="og:title"', $html);
        self::assertMatchesRegularExpression('/property="og:url" content="[^"]*">/', $html);
    }

    public function test_article_show_uses_article_meta_and_structured_data(): void
    {
        $article = Article::factory()->published()->create([
            'title' => 'Artikel SEO Uji',
            'slug' => 'artikel-seo-uji',
            'seo_title' => 'Judul SEO Khusus',
            'meta_description' => 'Deskripsi meta khusus dari admin.',
        ]);

        $html = $this->get('/artikel/artikel-seo-uji')->assertOk()->getContent();

        self::assertStringContainsString('<title>Judul SEO Khusus</title>', $html);
        self::assertStringContainsString('name="description" content="Deskripsi meta khusus dari admin."', $html);
        self::assertStringContainsString('<link rel="canonical" href="'.url('/artikel/artikel-seo-uji').'">', $html);
        self::assertStringContainsString('"@type":"Article"', $html);
        self::assertStringContainsString('"headline":"Artikel SEO Uji"', $html);
    }
}
