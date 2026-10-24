<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_guest_cannot_write(): void
    {
        $this->post('/admin/articles', ['title' => 'x'])->assertRedirect('/login');
    }

    public function test_non_admin_cannot_write(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post('/admin/articles', ['title' => 'x'])->assertForbidden();
    }

    public function test_store_sanitizes_content_before_persistence(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'Judul Artikel Uji Sanitasi',
            'excerpt' => 'Ringkasan singkat artikel uji.',
            'content' => '<table><tbody><tr><td>Lebar sekali</td></tr></tbody></table><p onclick="evil()">Paragraf aman.</p><script>alert("hack")</script><a href="javascript:alert(1)">link</a><a href="https://varilevke.test/x">ok</a>',
            'status' => 'draft',
        ]);

        $article = Article::query()->where('slug', 'judul-artikel-uji-sanitasi')->firstOrFail();

        $response->assertRedirect(route('admin.articles.edit', $article));

        // Dangerous content is stripped at the trust boundary, before the DB.
        self::assertStringNotContainsString('<script', $article->content);
        self::assertStringNotContainsString('alert(', $article->content);
        self::assertStringNotContainsString('onclick', $article->content);
        self::assertStringNotContainsString('javascript:', $article->content);
        // Legitimate content survives, and wide tables are wrapped safely.
        self::assertStringContainsString('<p>Paragraf aman.</p>', $article->content);
        self::assertStringContainsString('<div class="article-table-scroll">', $article->content);
        self::assertStringContainsString('Lebar sekali', $article->content);
        self::assertStringContainsString('https://varilevke.test/x', $article->content);

        // Word count is measured server-side from the sanitized content.
        self::assertSame(4, $article->word_count);
        self::assertSame(1, $article->reading_time);
        self::assertSame('draft', $article->status);
        self::assertNull($article->published_at);
    }

    public function test_store_generates_unique_slug_from_title(): void
    {
        $payload = [
            'title' => 'Artikel Sama Judulnya',
            'excerpt' => 'Ringkasan.',
            'content' => '<p>Isi pertama.</p>',
            'status' => 'draft',
        ];

        $this->actingAs($this->admin)->post('/admin/articles', $payload)->assertSessionDoesntHaveErrors();
        $this->actingAs($this->admin)->post('/admin/articles', $payload)->assertSessionDoesntHaveErrors();

        self::assertSame(2, Article::query()->where('slug', 'like', 'artikel-sama-judulnya%')->count());
    }

    public function test_store_rejects_invalid_slug_format(): void
    {
        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'Slug Rusak Uji',
            'excerpt' => 'Ringkasan.',
            'content' => '<p>isi</p>',
            'status' => 'draft',
            'slug' => 'Bad Slug!!',
        ])->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_kecamatan_from_another_city(): void
    {
        $cityA = City::factory()->create();
        $cityB = City::factory()->create();
        $kecamatanOfB = Kecamatan::factory()->create(['city_id' => $cityB->id]);

        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'Wilayah Salah Uji',
            'excerpt' => 'Ringkasan.',
            'content' => '<p>isi</p>',
            'status' => 'draft',
            'city_id' => $cityA->id,
            'kecamatan_id' => $kecamatanOfB->id,
        ])->assertSessionHasErrors('kecamatan_id');

        self::assertSame(0, Article::count());
    }

    public function test_store_accepts_kecamatan_in_same_city(): void
    {
        $city = City::factory()->create();
        $kecamatan = Kecamatan::factory()->create(['city_id' => $city->id]);

        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'Wilayah Benar Uji',
            'excerpt' => 'Ringkasan.',
            'content' => '<p>isi</p>',
            'status' => 'draft',
            'city_id' => $city->id,
            'kecamatan_id' => $kecamatan->id,
        ])->assertSessionDoesntHaveErrors();

        $article = Article::query()->where('slug', 'wilayah-benar-uji')->firstOrFail();
        self::assertSame($kecamatan->id, $article->kecamatan_id);
    }

    public function test_update_publishes_and_stamps_publication_time(): void
    {
        $article = Article::factory()->create(['status' => 'draft', 'published_at' => null]);

        $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => 'Ringkasan terbit.',
            'content' => '<p>Isi versi terbit.</p>',
            'status' => 'published',
        ])->assertSessionDoesntHaveErrors();

        $article->refresh();

        self::assertSame('published', $article->status);
        self::assertNotNull($article->published_at);
    }

    public function test_faq_line_fields_are_paired_into_faqs(): void
    {
        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'FAQ Uji Artikel',
            'excerpt' => 'Ringkasan.',
            'content' => '<p>Isi FAQ.</p>',
            'status' => 'draft',
            'faq_questions' => "Apa itu K3?\n\nBiaya berapa?",
            'faq_answers' => "Keselamatan dan Kesehatan Kerja.\nTanggal lahir?",
        ])->assertSessionDoesntHaveErrors();

        $article = Article::query()->where('slug', 'faq-uji-artikel')->firstOrFail();

        self::assertSame([
            ['question' => 'Apa itu K3?', 'answer' => 'Keselamatan dan Kesehatan Kerja.'],
            ['question' => 'Biaya berapa?', 'answer' => 'Tanggal lahir?'],
        ], $article->faqs);
    }

    public function test_update_links_catalog_item(): void
    {
        $item = CatalogItem::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => 'Ringkasan.',
            'content' => $article->content,
            'status' => 'draft',
            'catalog_item_id' => $item->id,
        ])->assertSessionDoesntHaveErrors();

        self::assertSame($item->id, $article->refresh()->catalog_item_id);
    }

    public function test_destroy_deletes_the_article(): void
    {
        $article = Article::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        self::assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
