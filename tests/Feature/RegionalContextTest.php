<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regional context must survive the whole navigation path:
 * wilayah index -> city -> kecamatan -> catalog -> detail, plus the safe
 * fallbacks (generic detail, invalid identifiers, empty states).
 */
class RegionalContextTest extends TestCase
{
    use RefreshDatabase;

    private function catalogCity(): City
    {
        return City::factory()->create([
            'name' => 'Surabaya Uji',
            'slug' => 'kota-surabaya-uji',
            'province' => 'Jawa Timur',
            'classification' => 'Kota',
        ]);
    }

    public function test_regional_index_renders_full_layout_grouped_by_province(): void
    {
        City::factory()->create(['name' => 'Kota A Uji', 'slug' => 'kota-a-uji', 'province' => 'Aceh']);
        City::factory()->create(['name' => 'Kota B Uji', 'slug' => 'kota-b-uji', 'province' => 'Bali']);

        $html = $this->get('/wilayah')->assertOk()->getContent();

        // Full document shell (the old bug: a bare <x-breadcrumb> fragment).
        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('link rel="stylesheet"', $html);
        self::assertStringContainsString('Aceh', $html);
        self::assertStringContainsString('Bali', $html);
        self::assertStringContainsString('Wilayah Layanan', $html);
    }

    public function test_city_catalog_index_preserves_city_context(): void
    {
        $city = $this->catalogCity();
        CatalogItem::factory()->create(['name' => 'Pelatihan Konteks Uji', 'slug' => 'pelatihan-konteks-uji', 'source_order' => 950]);

        $response = $this->get('/wilayah/kota-surabaya-uji/pelatihan')->assertOk();

        $response->assertSee('Kota Surabaya Uji');
        $response->assertSee('Pelatihan Konteks Uji');
        $response->assertSee('/wilayah/kota-surabaya-uji/pelatihan/pelatihan-konteks-uji');
    }

    public function test_city_catalog_detail_preserves_city_context(): void
    {
        $city = $this->catalogCity();
        CatalogItem::factory()->create(['name' => 'Pelatihan Detil Uji', 'slug' => 'pelatihan-detil-uji', 'source_order' => 951]);

        $html = $this->get('/wilayah/kota-surabaya-uji/pelatihan/pelatihan-detil-uji')->assertOk()->getContent();

        self::assertStringContainsString('<title>Pelatihan Detil Uji - Pelatihan di Kota Surabaya Uji | Varilevke</title>', $html);
        self::assertStringContainsString('Kota Surabaya Uji', $html);
        // Back to the regional catalog index for this city.
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/pelatihan"', $html);
        self::assertStringNotContainsString('<?php echo', $html);
    }

    public function test_kecamatan_page_links_catalog_with_kecamatan_context(): void
    {
        $city = $this->catalogCity();
        $kecamatan = Kecamatan::factory()->create(['city_id' => $city->id, 'name' => 'Asem Uji', 'slug' => 'asem-uji']);

        $html = $this->get('/wilayah/kota-surabaya-uji/asem-uji')->assertOk()->getContent();

        self::assertStringContainsString('Kecamatan Asem Uji', $html);
        self::assertStringContainsString('Kota Surabaya Uji', $html);
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/asem-uji/pelatihan"', $html);
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/asem-uji/jasa"', $html);
    }

    public function test_kecamatan_catalog_detail_preserves_kecamatan_context(): void
    {
        $city = $this->catalogCity();
        $kecamatan = Kecamatan::factory()->create(['city_id' => $city->id, 'name' => 'Asem Uji', 'slug' => 'asem-uji']);
        CatalogItem::factory()->create(['name' => 'Pelatihan Kec Uji', 'slug' => 'pelatihan-kec-uji', 'source_order' => 952]);

        $html = $this->get('/wilayah/kota-surabaya-uji/asem-uji/pelatihan/pelatihan-kec-uji')->assertOk()->getContent();

        self::assertStringContainsString('Kecamatan Asem Uji', $html);
        self::assertStringContainsString('Kota Surabaya Uji', $html);
        // Kecamatan-contextual links must stay under the same kecamatan.
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/asem-uji/pelatihan/pelatihan-kec-uji', $html);
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/asem-uji/pelatihan"', $html);
        self::assertStringNotContainsString('<?php echo', $html);
    }

    public function test_kecamatan_detail_wa_prefill_carries_region(): void
    {
        $city = $this->catalogCity();
        Kecamatan::factory()->create(['city_id' => $city->id, 'name' => 'Asem Uji', 'slug' => 'asem-uji']);
        CatalogItem::factory()->create(['name' => 'Pelatihan WA Uji', 'slug' => 'pelatihan-wa-uji', 'source_order' => 953]);

        $html = $this->get('/wilayah/kota-surabaya-uji/asem-uji/pelatihan/pelatihan-wa-uji')->assertOk()->getContent();

        // WA deep-links for CTA buttons must pre-fill the kecamatan name.
        self::assertStringContainsString('Pelatihan%20WA%20Uji', $html);
        self::assertStringContainsString('Asem%20Uji', $html);
    }

    public function test_kecamatan_mismatch_is_404(): void
    {
        $cityA = City::factory()->create(['slug' => 'kota-a-uji-2']);
        City::factory()->create(['slug' => 'kota-b-uji-2']);
        Kecamatan::factory()->create(['city_id' => City::query()->where('slug', 'kota-b-uji-2')->value('id'), 'slug' => 'kec-di-b-2']);

        $this->get('/wilayah/kota-a-uji-2/kec-di-b-2')->assertNotFound();
        $this->get('/wilayah/kota-a-uji-2/kec-di-b-2/pelatihan')->assertNotFound();
        $this->get('/wilayah/kota-a-uji-2/kec-di-b-2/pelatihan/pelatihan-ahli-k3-umum')->assertNotFound();
    }

    public function test_city_catalog_of_unknown_city_is_404(): void
    {
        $this->get('/wilayah/kota-tidak-ada-xyz/pelatihan')->assertNotFound();
    }

    public function test_generic_detail_page_remains_valid_without_region(): void
    {
        CatalogItem::factory()->create(['name' => 'Pelatihan Generik Uji', 'slug' => 'pelatihan-generik-uji', 'source_order' => 954]);

        $response = $this->get('/pelatihan/pelatihan-generik-uji')->assertOk();

        $response->assertSee('Pelatihan Generik Uji');
        // No fabricated region: no "Kecamatan" badge in the hero area.
        self::assertStringNotContainsString('KECAMATAN', $response->getContent());
    }

    public function test_kecamatan_catalog_detail_of_unknown_item_is_404(): void
    {
        $city = $this->catalogCity();
        Kecamatan::factory()->create(['city_id' => $city->id, 'name' => 'Asem Uji', 'slug' => 'asem-uji']);

        $this->get('/wilayah/kota-surabaya-uji/asem-uji/pelatihan/tidak-ada-item-uji')->assertNotFound();
    }

    public function test_city_page_shows_featured_items_not_the_full_dump(): void
    {
        $city = $this->catalogCity();
        // 7 pelatihan items: only the first 6 (by source_order) are featured.
        for ($i = 100; $i <= 106; $i++) {
            CatalogItem::factory()->create([
                'name' => $i === 106 ? 'Pelatihan Jauh Uji' : 'Pelatihan Pertama Uji '.$i,
                'slug' => $i === 106 ? 'pelatihan-jauh-uji' : 'pelatihan-pertama-uji-'.$i,
                'source_order' => $i,
            ]);
        }

        $html = $this->get('/wilayah/kota-surabaya-uji')->assertOk()->getContent();

        // Featured catalog subset is present with city context…
        self::assertStringContainsString('Pelatihan Pertama Uji 100', $html);
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/pelatihan/', $html);
        // …but the page must not render every catalog card (no 272-card dump).
        self::assertStringNotContainsString('Pelatihan Jauh Uji', $html);
        self::assertStringContainsString('/wilayah/kota-surabaya-uji/pelatihan"', $html);
    }

    public function test_articles_index_renders_full_layout(): void
    {
        $html = $this->get('/artikel')->assertOk()->getContent();

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('link rel="stylesheet"', $html);
        self::assertStringContainsString('Artikel', $html);
    }

    public function test_invalid_kecamatan_in_context_routes_404(): void
    {
        $city = $this->catalogCity();

        $this->get('/wilayah/kota-surabaya-uji/kec-palsu-anti-duplikat-uji/pelatihan')->assertNotFound();
        $this->get('/wilayah/kota-surabaya-uji/kec-palsu-anti-duplikat-uji/pelatihan/pelatihan-ahli-k3-umum')->assertNotFound();
    }
}
