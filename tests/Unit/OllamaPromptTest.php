<?php

namespace Tests\Unit;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaPromptTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSuccessfulOllama(): void
    {
        config([
            'services.ollama.base_url' => 'http://dummy-ollama.local',
            'services.ollama.model' => 'dummy-model',
        ]);

        $content = str_repeat('kata ', 7500);
        Http::fake(['*' => Http::response([
            'message' => ['content' => json_encode([
                'title' => 'Judul Test Varilevke',
                'slug' => 'judul-test-varilevke',
                'excerpt' => 'Excerpt test',
                'content' => $content,
                'seo_title' => 'SEO Title Test | Varilevke',
                'meta_description' => 'Meta Description Test',
                'suggested_faqs' => [],
                'image_brief' => [],
            ])],
        ], 200)]);
    }

    public function test_training_prompt_carries_competency_and_factual_guardrails(): void
    {
        $this->fakeSuccessfulOllama();

        $item = CatalogItem::factory()->create([
            'name' => 'Operator Forklift Kelas II',
            'slug' => 'operator-forklift-kelas-ii',
            'category' => 'pelatihan',
        ]);

        $city = City::factory()->create([
            'name' => 'Surabaya',
            'slug' => 'surabaya',
            'province' => 'Jawa Timur',
            'classification' => 'Kota',
        ]);

        $service = app(OllamaService::class);
        $service->generateArticle([
            'catalog_item_id' => $item->id,
            'city_id' => $city->id,
            'word_count' => 8000,
        ]);

        Http::assertSent(function (object $request): bool {
            $system = $request['messages'][0]['content'];
            $user = $request['messages'][1]['content'];

            return str_contains($system, 'Operator Forklift Kelas II')
                && str_contains($system, 'PEDOMAN KONTEN ARTIKEL PELATIHAN')
                && str_contains($system, 'DILARANG mengarang lembaga sertifikasi')
                && str_contains($system, 'DILARANG mengarang harga/biaya')
                && str_contains($system, 'DILARANG mengarang alamat kantor fisik cabang lokal')
                && str_contains($user, 'Operator Forklift Kelas II')
                && str_contains($user, 'Kota Surabaya');
        });
    }

    public function test_service_prompt_carries_scope_and_non_guarantee_rules(): void
    {
        $this->fakeSuccessfulOllama();

        $item = CatalogItem::factory()->create([
            'name' => 'Sertifikat Laik Fungsi (SLF)',
            'slug' => 'sertifikat-laik-fungsi-slf',
            'category' => 'jasa',
        ]);

        $city = City::factory()->create([
            'name' => 'Gresik',
            'slug' => 'kab-gresik',
            'province' => 'Jawa Timur',
            'classification' => 'Kabupaten',
        ]);

        $service = app(OllamaService::class);
        $service->generateArticle([
            'catalog_item_id' => $item->id,
            'city_id' => $city->id,
            'word_count' => 8000,
        ]);

        Http::assertSent(function (object $request): bool {
            $system = $request['messages'][0]['content'];
            $user = $request['messages'][1]['content'];

            return str_contains($system, 'Sertifikat Laik Fungsi (SLF)')
                && str_contains($system, 'PEDOMAN KONTEN ARTIKEL JASA')
                && str_contains($system, 'DILARANG menjamin kepatuhan hukum instan atau kelulusan mutlak')
                && str_contains($user, 'Sertifikat Laik Fungsi (SLF)')
                && str_contains($user, 'Kategori Katalog: jasa');
        });
    }

    public function test_regional_prompt_verifies_bps_hierarchy(): void
    {
        $this->fakeSuccessfulOllama();

        $city = City::factory()->create([
            'name' => 'Surabaya',
            'slug' => 'surabaya',
            'province' => 'Jawa Timur',
            'classification' => 'Kota',
        ]);

        $kecamatan = Kecamatan::factory()->create([
            'city_id' => $city->id,
            'name' => 'Rungkut',
            'slug' => 'rungkut',
        ]);

        $service = app(OllamaService::class);
        $service->generateArticle([
            'city_id' => $city->id,
            'kecamatan_id' => $kecamatan->id,
            'word_count' => 8000,
        ]);

        Http::assertSent(function (object $request) use ($city, $kecamatan): bool {
            $system = $request['messages'][0]['content'];
            $user = $request['messages'][1]['content'];

            return str_contains($system, "Kecamatan {$kecamatan->name}")
                && str_contains($system, "{$city->label()}")
                && str_contains($user, "Kecamatan {$kecamatan->name}, {$city->label()}, Provinsi {$city->province}");
        });
    }

    public function test_prompts_exclude_legacy_brand_wording(): void
    {
        $this->fakeSuccessfulOllama();

        $service = app(OllamaService::class);
        $service->generateArticle([
            'city_name' => 'Surabaya',
            'word_count' => 7500,
        ]);

        Http::assertSent(function (object $request): bool {
            $prompts = $request['messages'][0]['content'].' '.$request['messages'][1]['content'];

            return ! str_contains($prompts, 'Training K3 Listrik Corp')
                && ! str_contains($prompts, 'Training Ahli K3 Umum')
                && ! str_contains($prompts, 'Alat Berat');
        });
    }

    public function test_carbon_item_classified_as_pelatihan(): void
    {
        $this->fakeSuccessfulOllama();

        $item = CatalogItem::factory()->create([
            'name' => 'Jasa Lingkungan Karbon',
            'slug' => 'jasa-lingkungan-karbon',
            'category' => 'pelatihan',
            'review_required' => true,
        ]);

        $service = app(OllamaService::class);
        $service->generateArticle([
            'catalog_item_id' => $item->id,
            'word_count' => 8000,
        ]);

        Http::assertSent(function (object $request): bool {
            $user = $request['messages'][1]['content'];

            // Must preserve official catalog classification "pelatihan"
            return str_contains($user, '- Layanan/Program Resmi: Jasa Lingkungan Karbon')
                && str_contains($user, '- Kategori Katalog: pelatihan');
        });
    }
}
