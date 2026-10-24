<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Services\Catalog\CatalogImporter;
use App\Services\Catalog\CatalogImportException;
use App\Services\Catalog\CatalogManifest;
use App\Services\Catalog\CatalogManifestException;
use App\Services\Catalog\ImportReport;
use App\Support\WhatsApp;
use Database\Seeders\CatalogItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private function import(array $params = []): ImportReport
    {
        $path = $params['manifest_path'] ?? null;

        $manifest = $path !== null
            ? CatalogManifest::fromFile($path)
            : CatalogManifest::fromFile((string) config('catalog.manifest_path'));

        return (new CatalogImporter($manifest))->import();
    }

    public function test_imports_the_exact_source_counts(): void
    {
        $report = $this->import();

        self::assertSame(227, CatalogItem::query()->category(CatalogItem::CATEGORY_PELATIHAN)->count());
        self::assertSame(45, CatalogItem::query()->category(CatalogItem::CATEGORY_JASA)->count());
        self::assertSame(272, CatalogItem::query()->count());
        self::assertSame(272, count($report->inserted));
    }

    public function test_jasa_lingkungan_karbon_stays_pelatihan_and_marked_for_review(): void
    {
        $this->import();

        $item = CatalogItem::query()
            ->where('category', CatalogItem::CATEGORY_PELATIHAN)
            ->where('source_order', 225)
            ->firstOrFail();

        self::assertSame('Jasa Lingkungan Karbon', $item->name);
        self::assertTrue($item->review_required);
        self::assertStringContainsString('konfirmasi', (string) $item->review_note);
    }

    public function test_source_names_are_preserved_byte_for_byte(): void
    {
        $this->import();

        $sum = 0;
        $expected = json_decode((string) file_get_contents((string) config('catalog.manifest_path')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($expected['items'] as $row) {
            $model = CatalogItem::query()
                ->where('category', $row['category'])
                ->where('source_order', $row['source_order'])
                ->firstOrFail();

            self::assertSame($row['name'], $model->name, "Mismatch at {$row['category']} #{$row['source_order']}");
            $sum++;
        }

        self::assertSame(272, $sum);

        // Invisible leading character on the "Jasa Publikasi Jurnal Sinta…" item.
        $sinta = CatalogItem::query()->where('category', 'jasa')->where('source_order', 27)->firstOrFail();

        self::assertSame("\u{2060}Jasa Publikasi Jurnal Sinta 1, 2, 3, 4 & 5", $sinta->name);
    }

    public function test_source_ordering_is_preserved_per_category(): void
    {
        $this->import();

        $pelatihan = CatalogItem::query()->category('pelatihan')->orderBy('source_order')->pluck('name');
        $jasa = CatalogItem::query()->category('jasa')->orderBy('source_order')->pluck('name');

        self::assertSame('Ahli K3 Umum', $pelatihan->first());
        self::assertSame('Cathodic Protection', $pelatihan->last());
        self::assertSame('Jasa UKL-UPL AMDAL PERTEK RINTEK UJI LINGKUNGAN', $jasa->first());
        self::assertSame('Jasa Sertifikat HACCP', $jasa->last());
    }

    public function test_second_run_is_idempotent_and_touches_nothing(): void
    {
        $this->import();

        $before = CatalogItem::query()->select(['source_order', 'category', 'name', 'slug'])
            ->orderBy('category')->orderBy('source_order')->get()->toJson(JSON_UNESCAPED_UNICODE);

        $second = $this->import();

        self::assertSame([], $second->inserted, 'Nothing may be inserted on a repeat run.');
        self::assertSame(272, $second->unchanged);
        self::assertSame(0, $second->updated);

        self::assertSame($before, CatalogItem::query()->select(['source_order', 'category', 'name', 'slug'])
            ->orderBy('category')->orderBy('source_order')->get()->toJson(JSON_UNESCAPED_UNICODE));
    }

    public function test_import_aborts_when_manifest_counts_disagree_with_expectations(): void
    {
        config(['catalog.expected_counts' => ['pelatihan' => 1, 'jasa' => 1, 'total' => 2]]);

        $this->expectException(CatalogImportException::class);
        $this->expectExceptionMessage('Refusing');

        $this->import();
    }

    public function test_import_aborts_when_a_fixture_fails_counts_validation(): void
    {
        config(['catalog.expected_counts' => ['pelatihan' => 227, 'jasa' => 45, 'total' => 272]]);

        $this->expectException(CatalogManifestException::class);

        $this->import(['manifest_path' => __DIR__.'/../fixtures/catalog-manifest-bad-counts.json']);
    }

    public function test_import_reports_name_drift_as_conflict_instead_of_overwriting(): void
    {
        $this->import();

        // Simulate a human editing a stored name after import: the source and
        // the database now disagree. The importer must refuse, list the
        // conflict, and never overwrite the admin's data.
        CatalogItem::query()
            ->where('category', CatalogItem::CATEGORY_PELATIHAN)
            ->where('source_order', 1)
            ->update(['name' => 'Ahli K3 Umum (Nama Diedit Admin)']);

        try {
            $this->import();
            self::fail('Expected CatalogImportException.');
        } catch (CatalogImportException $e) {
            $errors = implode("\n", $e->conflicts);

            self::assertStringContainsString('name differs', $errors);
            self::assertStringContainsString('Nama Diedit Admin', $errors);
            self::assertStringContainsString('source_order 1', $errors);
        }

        // The edited row survived untouched (transaction rolled back), and the
        // importer never silently restored its own version of the name.
        self::assertSame(272, CatalogItem::count());
        self::assertDatabaseHas('catalog_items', ['name' => 'Ahli K3 Umum (Nama Diedit Admin)']);
        self::assertSame(0, CatalogItem::query()->where('name', 'Ahli K3 Umum')->count());
    }

    public function test_whatsapp_numbers_keep_leading_zeros_and_order(): void
    {
        $numbers = WhatsApp::numbers();

        self::assertSame([
            '08118500177',
            '081399810272',
            '087894580806',
            '087894580649',
        ], $numbers);

        self::assertSame('628118500177', WhatsApp::normalize('08118500177'));
        self::assertSame('https://wa.me/628118500177', WhatsApp::link('08118500177'));
        self::assertStringContainsString('Halo', WhatsApp::link('08118500177', 'Halo'));
    }

    public function test_seeder_imports_the_same_catalog(): void
    {
        $this->seed(CatalogItemSeeder::class);

        self::assertSame(272, CatalogItem::count());
    }
}
