<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\User;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiGenerateArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bind a mock OllamaService that returns the given response array.
     */
    protected function bindFakeOllama(array $response): void
    {
        $mock = $this->createMock(OllamaService::class);
        $mock->method('generateArticle')->willReturn($response);
        $this->app->instance(OllamaService::class, $mock);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(route('admin.articles.ai-generate'), [
            'word_count' => 7500,
        ])->assertStatus(401);
    }

    public function test_requires_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->postJson(route('admin.articles.ai-generate'), [
            'word_count' => 7500,
        ])->assertStatus(403);
    }

    public function test_rejects_word_count_below_minimum(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'word_count' => 5000, // below 6000
        ])->assertStatus(422);
    }

    public function test_rejects_word_count_above_maximum(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'word_count' => 12000, // above 10000
        ])->assertStatus(422);
    }

    public function test_rejects_invalid_city_slug(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'city_slug' => 'nonexistent-city-slug',
            'word_count' => 7500,
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_successful_generation_returns_article_data_synchronously(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $city = City::factory()->create([
            'name' => 'Surabaya',
            'slug' => 'surabaya',
        ]);

        $this->bindFakeOllama([
            'success' => true,
            'title' => 'Ahli K3 Umum di Surabaya',
            'slug' => 'ahli-k3-umum-di-surabaya',
            'excerpt' => 'Panduan lengkap sertifikasi Ahli K3 Umum untuk wilayah Surabaya.',
            'content' => '<p>Konten artikel lengkap pelatihan K3 di Surabaya.</p>',
            'seo_title' => 'Ahli K3 Umum Surabaya | Varilevke',
            'meta_description' => 'Sertifikasi Ahli K3 Umum profesional di Surabaya.',
            'suggested_faqs' => [['q' => 'Apa syarat pendaftaran?', 'a' => 'Pendidikan min D3/S1']],
            'image_brief' => [
                'concept' => 'Foto dokumentasi pelatihan K3 Surabaya',
                'english_prompt' => 'Documentary photo of safety training',
                'aspect_ratio' => '16:9',
                'recommended_alt' => 'Pelatihan Ahli K3 Umum Surabaya',
            ],
            'word_count' => 7500,
            'reading_time' => 38,
            'city_id' => $city->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'city_slug' => 'surabaya',
            'word_count' => 7500,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'title', 'slug', 'content', 'word_count'])
            ->assertJson(['success' => true, 'title' => 'Ahli K3 Umum di Surabaya']);

        // Synchronous draft preview: verify no background queue job or polling keys
        $this->assertArrayNotHasKey('job_id', $response->json());
        $this->assertArrayNotHasKey('status', $response->json());
    }

    public function test_generation_failure_returns_500_with_error(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->bindFakeOllama([
            'success' => false,
            'error' => 'Koneksi ke Ollama gagal atau timeout.',
        ]);

        $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'word_count' => 7500,
        ])->assertStatus(500)->assertJson([
            'success' => false,
            'error' => 'Koneksi ke Ollama gagal atau timeout.',
        ]);
    }

    public function test_successful_generation_with_catalog_item_and_city(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $city = City::factory()->create([
            'name' => 'Jakarta Selatan',
            'slug' => 'jakarta-selatan',
        ]);

        $catalog = CatalogItem::factory()->create([
            'name' => 'Pelatihan Ahli K3 Kimia',
            'slug' => 'pelatihan-ahli-k3-kimia',
            'category' => 'pelatihan',
        ]);

        $this->bindFakeOllama([
            'success' => true,
            'title' => 'Pelatihan Ahli K3 Kimia Jakarta Selatan',
            'slug' => 'pelatihan-ahli-k3-kimia-jakarta-selatan',
            'excerpt' => 'Ringkasan pelatihan kimia.',
            'content' => '<p>Detail lengkap materi kimia.</p>',
            'seo_title' => 'Pelatihan Ahli K3 Kimia Jakarta Selatan | Varilevke',
            'meta_description' => 'Panduan K3 Kimia di Jakarta Selatan.',
            'suggested_faqs' => [],
            'image_brief' => [],
            'catalog_item_id' => $catalog->id,
            'city_id' => $city->id,
            'word_count' => 8000,
            'reading_time' => 40,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.articles.ai-generate'), [
            'catalog_item_id' => $catalog->id,
            'city_id' => $city->id,
            'word_count' => 8000,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'catalog_item_id' => $catalog->id,
                'city_id' => $city->id,
            ]);
    }
}
