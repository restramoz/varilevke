<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The generation endpoint returns drafts only. Every failure mode must end in
 * an explicit 422/500 with a safe message; nothing is ever saved by the
 * preview itself — persistence happens exclusively through store()/update().
 */
class ArticleGeneratePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        config([
            'services.ai.enabled' => true,
            'services.ai.api_key' => 'test-key-that-is-not-real',
            'services.ai.base_url' => 'https://ai.example.test/v1',
            'services.ai.timeout' => 5,
            'services.ai.max_attempts' => 3,
        ]);
    }

    private function outlinePayload(): string
    {
        $sections = [];

        for ($i = 1; $i <= 8; $i++) {
            $sections[] = ['heading' => 'Bagian Uji '.$i, 'brief' => 'Arahan bagian '.$i, 'words' => 1000];
        }

        return json_encode([
            'title' => 'Judul Draf Generasi Uji',
            'summary' => 'Ringkasan draf yang cukup panjang untuk lolos validasi minimum lima puluh karakter. Isi kalimat tambahan.',
            'sections' => $sections,
            'faqs' => [
                ['question' => 'Pertanyaan uji satu?', 'answer' => 'Jawaban uji satu.'],
                ['question' => 'Pertanyaan uji dua?', 'answer' => 'Jawaban uji dua.'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function sectionPayload(string $heading, int $words): string
    {
        // Enough characters to pass the >= 50-char fragment validation, but
        // only 2 words each when a deliberate shortfall is being tested.
        $html = $words >= 100
            ? '<p>'.str_repeat('lorem ipsum dolor sit amet ', (int) ceil($words / 5)).'</p>'
            : '<p>'.str_repeat('a', 40).' '.str_repeat('b', 20).'</p>';

        return json_encode(['heading' => $heading, 'html' => $html], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  list<string>  $contents  provider message contents, in call order
     */
    private function fakeProvider(array $contents): void
    {
        $sequence = Http::sequence();

        foreach ($contents as $content) {
            $sequence->push(['choices' => [['message' => ['content' => $content]]]], 200);
        }

        Http::fake([
            'ai.example.test/*' => $sequence,
            'ai.example.test' => $sequence,
        ]);
    }

    public function test_valid_generation_returns_draft_and_never_saves(): void
    {
        $calls = [$this->outlinePayload()];

        for ($i = 1; $i <= 8; $i++) {
            $calls[] = $this->sectionPayload('Bagian Uji '.$i, 1005);
        }

        $this->fakeProvider($calls);

        $response = $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Pelatihan K3 Umum untuk pekerja baru',
        ]);

        $response->assertOk();

        $draft = $response->json('draft');

        self::assertSame('Judul Draf Generasi Uji', $draft['title']);
        self::assertGreaterThan(6000, $draft['word_count']);
        self::assertLessThan(10000, $draft['word_count']);
        self::assertStringContainsString('<h2>Bagian Uji 1</h2>', $draft['content']);
        self::assertSame([['question' => 'Pertanyaan uji satu?', 'answer' => 'Jawaban uji satu.'], ['question' => 'Pertanyaan uji dua?', 'answer' => 'Jawaban uji dua.']], $draft['faqs']);

        // Draft-only workflow: the preview endpoint must not persist anything.
        self::assertSame(0, Article::count());

        // Exactly one outline + eight section calls (no runaway duplicates).
        Http::assertSentCount(9);
    }

    public function test_provider_calls_use_committed_configuration(): void
    {
        $this->fakeProvider([$this->outlinePayload(), ...array_map(
            fn (int $i): string => $this->sectionPayload('Bagian Uji '.$i, 1005),
            range(1, 8)
        )]);

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji konfigurasi model',
        ])->assertOk();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://ai.example.test/v1/chat/completions')
            && $request->header('Authorization')[0] === 'Bearer test-key-that-is-not-real'
            && is_string($request->data()['model'] ?? null));
    }

    public function test_invalid_json_from_provider_fails_explicitly(): void
    {
        $this->fakeProvider(['<<< ini bukan json <<<']);

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji json rusak dari model',
        ])->assertStatus(422)
            ->assertJsonPath('error', fn (string $error): bool => str_contains($error, 'invalid JSON'));

        self::assertSame(0, Article::count());
    }

    public function test_empty_section_output_fails_explicitly(): void
    {
        $this->fakeProvider([
            $this->outlinePayload(),
            json_encode(['heading' => 'Bagian Uji 1', 'html' => ''], JSON_UNESCAPED_UNICODE),
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji bagian kosong dari model',
        ])->assertStatus(422)
            ->assertJsonPath('error', fn (string $error): bool => str_contains($error, 'validation'));

        self::assertSame(0, Article::count());
    }

    public function test_word_shortfall_fails_instead_of_claiming_success(): void
    {
        $calls = [$this->outlinePayload(), ...array_map(
            fn (int $i): string => $this->sectionPayload('Bagian Uji '.$i, 5),
            range(1, 8)
        )];

        $this->fakeProvider($calls);

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji artikel terlalu pendek',
        ])->assertStatus(422)
            ->assertJsonPath('error', fn (string $error): bool => str_contains($error, 'too short')
                && str_contains($error, 'minimum'));

        self::assertSame(0, Article::count());
    }

    public function test_provider_http_error_is_reported_safely(): void
    {
        Http::fake([
            'ai.example.test/*' => Http::sequence()
                ->push(['oops'], 503)
                ->push(['oops'], 503)
                ->push(['oops'], 503),
            'ai.example.test' => Http::sequence()
                ->push(['oops'], 503)
                ->push(['oops'], 503)
                ->push(['oops'], 503),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji provider mati total',
        ]);

        $response->assertStatus(422);

        $error = (string) $response->json('error');

        // Safe message: no secrets, no raw provider payload, no stack traces.
        self::assertStringContainsString('attempts', $error);
        self::assertStringContainsString('provider service error', $error);
        self::assertStringNotContainsString('test-key-that-is-not-real', $error);
        self::assertStringNotContainsString('oops', $error);

        self::assertSame(0, Article::count());
    }

    public function test_word_count_outside_band_is_rejected_by_validation(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji band kata',
            'word_count' => 4000,
        ])->assertJsonValidationErrors('word_count');

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji band kata',
            'word_count' => 12000,
        ])->assertJsonValidationErrors('word_count');

        self::assertSame(0, Article::count());
    }

    public function test_ai_disabled_reports_not_configured(): void
    {
        config(['services.ai.enabled' => false]);

        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'Topik uji tanpa konfigurasi AI',
        ])->assertStatus(422)
            ->assertJsonPath('error', fn (string $error): bool => str_contains($error, 'not configured'));

        self::assertSame(0, Article::count());
    }

    public function test_topic_too_short_is_rejected_by_validation(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.articles.generate-preview'), [
            'topic' => 'aju',
        ])->assertJsonValidationErrors('topic');
    }
}
