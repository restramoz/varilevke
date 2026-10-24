<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_catalog_overview_and_contacts(): void
    {
        CatalogItem::factory()->create(['name' => 'Pelatihan Uji Satu', 'slug' => 'pelatihan-uji-satu']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Jelajahi');
        $response->assertSee('Lihat');

        // WhatsApp numbers render with leading zeros intact, exactly four.
        $html = $response->getContent();

        foreach (['08118500177', '081399810272', '087894580806', '087894580649'] as $number) {
            self::assertMatchesRegularExpression('/>\s*'.preg_quote($number, '/').'\s*<\/a>/', $html);
            self::assertStringContainsString('https://wa.me/62'.substr($number, 1), $html);
        }

        preg_match_all('/wa\.me\/(\d+)/', $html, $links);

        // Distinct wa.me targets must include all four configured numbers (international 62… form).
        // Order may vary because header hotline is randomized; we only assert set equality.
        $expected = array_map(
            fn (string $number): string => '62'.substr($number, 1),
            ['08118500177', '081399810272', '087894580806', '087894580649']
        );
        $actualUnique = array_values(array_unique($links[1]));
        sort($expected);
        sort($actualUnique);
        self::assertSame($expected, $actualUnique);
    }

    public function test_catalog_index_searches_within_category(): void
    {
        CatalogItem::factory()->create(['name' => 'Pelatihan Ahli Uji', 'slug' => 'pelatihan-ahli-uji', 'source_order' => 900]);
        CatalogItem::factory()->create(['name' => 'Pelatihan Lain Uji', 'slug' => 'pelatihan-lain-uji', 'source_order' => 901]);

        $this->get('/pelatihan?q=Ahli')->assertOk()->assertSee('Pelatihan Ahli Uji')->assertDontSee('Pelatihan Lain Uji');
    }

    public function test_catalog_detail_enforces_category_match(): void
    {
        CatalogItem::factory()->create([
            'name' => 'Ahli K3 Umum',
            'slug' => 'ahli-k3-umum',
            'category' => 'pelatihan',
        ]);
        CatalogItem::factory()->category('jasa')->create([
            'name' => 'Jasa Sertifikat HACCP',
            'slug' => 'jasa-sertifikat-haccp',
            'source_order' => 900,
        ]);

        $this->get('/pelatihan/ahli-k3-umum')->assertOk()->assertSee('Ahli K3 Umum');
        $this->get('/pelatihan/jasa-sertifikat-haccp')->assertNotFound();
        $this->get('/jasa/ahli-k3-umum')->assertNotFound();
    }

    public function test_unknown_catalog_slugs_are_404(): void
    {
        $this->get('/pelatihan/tidak-ada-item-ini')->assertNotFound();
    }

    public function test_article_index_shows_only_published(): void
    {
        Article::factory()->published()->create(['title' => 'Artikel Terbit Uji', 'slug' => 'artikel-terbit-uji']);
        Article::factory()->create(['title' => 'Artikel Draf Uji', 'slug' => 'artikel-draf-uji']);

        $html = $this->get('/artikel')->assertOk()->getContent();

        self::assertStringContainsString('Artikel Terbit Uji', $html);
        self::assertStringNotContainsString('Artikel Draf Uji', $html);
    }

    public function test_article_detail_shows_published_and_404s_drafts(): void
    {
        $article = Article::factory()->published()->create([
            'title' => 'Artikel Detail Uji',
            'slug' => 'artikel-detail-uji',
            'content' => '<h2>Bagian Uji</h2><p>Isi paragraf artikel.</p>'.
                '<table><thead><tr><th>Kolom A</th></tr></thead><tbody><tr><td>Nilai A</td></tr></tbody></table>',
        ]);

        $html = $this->get('/artikel/artikel-detail-uji')->assertOk()->getContent();

        self::assertStringContainsString('Isi paragraf artikel.', $html);
        // The input-time sanitizer owns the wrapper; output renders stored HTML.
        // (Wrapper behavior itself is covered in HtmlSanitizerServiceTest and
        // through the admin store path in ArticleAdminCrudTest.)
        self::assertStringContainsString('<table>', $html);
    }

    public function test_draft_articles_are_invisible_publicly(): void
    {
        $article = Article::factory()->create(['title' => 'Draf Tersembunyi', 'slug' => 'draf-tersembunyi']);

        $this->get('/artikel/draf-tersembunyi')->assertNotFound();
    }

    public function test_regional_city_and_kecamatan_pages(): void
    {
        $city = City::factory()->create(['name' => 'Kota Uji', 'slug' => 'kota-uji', 'province' => 'Jawa Timur', 'classification' => 'Kota']);
        $kecamatan = Kecamatan::factory()->create(['city_id' => $city->id, 'name' => 'Kec Uji', 'slug' => 'kec-uji']);

        $this->get('/wilayah')->assertOk()->assertSee('Kota Uji');
        $this->get('/wilayah/kota-uji')->assertOk()->assertSee('Kec Uji');

        $html = $this->get('/wilayah/kota-uji/kec-uji')->assertOk()->getContent();
        self::assertStringContainsString('Kecamatan Kec Uji', $html);
        self::assertStringContainsString('Kota Uji', $html);
    }

    public function test_kecamatan_slug_must_belong_to_the_requested_city(): void
    {
        $cityA = City::factory()->create(['slug' => 'kota-a-uji']);
        $cityB = City::factory()->create(['slug' => 'kota-b-uji']);
        Kecamatan::factory()->create(['city_id' => $cityB->id, 'slug' => 'kec-di-b']);

        $this->get('/wilayah/kota-a-uji/kec-di-b')->assertNotFound();
    }

    public function test_article_links_regional_and_catalog_pages(): void
    {
        $city = City::factory()->create(['slug' => 'kota-tertaut']);
        CatalogItem::factory()->create(['slug' => 'item-tertaut', 'category' => 'pelatihan', 'name' => 'Item Tertaut', 'source_order' => 940]);

        Article::factory()->published()->create([
            'slug' => 'artikel-tertaut-uji',
            'catalog_item_id' => CatalogItem::query()->where('slug', 'item-tertaut')->value('id'),
            'city_id' => $city->id,
        ]);

        $response = $this->get('/artikel/artikel-tertaut-uji')->assertOk();

        $response->assertSee(route('regional.city', $city));
        $response->assertSee('Item Tertaut');
    }
}
