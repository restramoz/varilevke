<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\Ai\ArticleGeneratorService;
use App\Services\Ai\GenerationException;
use App\Services\Ai\GenerationRequest;
use App\Services\HtmlSanitizerService;
use App\Services\ImageUploadService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleAdminController extends Controller
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly ArticleGeneratorService $generator,
        private readonly OllamaService $ollama,
    ) {}

    /**
     * Display a listing of articles with optional filters.
     */
    public function index(Request $request): View
    {
        $query = Article::query()->with([
            'catalogItem:id,name,category',
            'city:id,name,slug,classification,province',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->query('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }

        $articles = $query->paginate(20)->withQueryString();

        return view('admin.articles.index', compact('articles'));
    }

    /**
     * Show the form for creating a new article (with AI generator).
     */
    public function create(): View
    {
        return view('admin.articles.create', $this->formData());
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(
        Request $request,
        ImageUploadService $imageUploader
    ): RedirectResponse {
        $this->preprocessActionsAndJson($request);

        $validated = $this->validateArticleData($request, null);
        $validated['content'] = $this->sanitizer->sanitize((string) $validated['content']);

        // Auto-generate unique slug if missing
        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $originalSlug = $slug ?: 'artikel';
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $uploadResult = $imageUploader->storeImage($request->file('featured_image'));
            if (! $uploadResult['success']) {
                return back()->withInput()->withErrors(['featured_image' => $uploadResult['error']]);
            }
            $validated['featured_image'] = $uploadResult['path'];
        }

        $computedData = $this->withComputedFields($validated);

        try {
            DB::beginTransaction();
            $article = Article::create($computedData);
            DB::commit();

            return redirect()->route('admin.articles.edit', $article)
                ->with('status', 'Artikel berhasil disimpan dengan status '.ucfirst($article->status).'.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan artikel: '.$e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(Article $article): View
    {
        return view('admin.articles.edit', array_merge(['article' => $article], $this->formData()));
    }

    /**
     * Update the specified article in storage.
     */
    public function update(
        Request $request,
        Article $article,
        ImageUploadService $imageUploader
    ): RedirectResponse {
        $this->preprocessActionsAndJson($request);

        $validated = $this->validateArticleData($request, $article);
        $validated['content'] = $this->sanitizer->sanitize((string) $validated['content']);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $originalSlug = $slug ?: 'artikel';
        $counter = 1;
        while (Article::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $uploadResult = $imageUploader->storeImage($request->file('featured_image'));
            if (! $uploadResult['success']) {
                return back()->withInput()->withErrors(['featured_image' => $uploadResult['error']]);
            }
            $validated['featured_image'] = $uploadResult['path'];
        } else {
            unset($validated['featured_image']);
        }

        // Preserve existing image_brief and faqs if omitted or null
        if (! array_key_exists('image_brief', $validated) || ($validated['image_brief'] === null && ! empty($article->image_brief) && ! $request->has('image_brief'))) {
            unset($validated['image_brief']);
        }
        if (! array_key_exists('faqs', $validated) || ($validated['faqs'] === null && ! empty($article->faqs) && ! $request->has('faqs'))) {
            unset($validated['faqs']);
        }

        $computedData = $this->withComputedFields($validated, $article);

        try {
            DB::beginTransaction();
            $article->update($computedData);
            DB::commit();

            return redirect()->route('admin.articles.edit', $article)
                ->with('status', 'Artikel berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui artikel: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', 'Artikel berhasil dihapus.');
    }

    /**
     * Return JSON options for kecamatans scoped to a city.
     */
    public function kecamatans(City $city): JsonResponse
    {
        return response()->json(
            $city->kecamatans()->orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * AI generation endpoint using Ollama — synchronous, no queue required.
     * Ported faithfully from Training Ahli K3 Umum.
     * Does NOT save half-baked records into database!
     */
    public function aiGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_slug' => ['nullable', 'string'],
            'district_slug' => ['nullable', 'string'],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'kecamatan_id' => [
                'nullable',
                Rule::exists('kecamatans', 'id'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateKecamatanTarget($request, $value, $fail),
            ],
            // Word count bounds: 6,000 - 10,000 words
            'word_count' => ['required', 'integer', 'min:6000', 'max:10000'],
            'topic' => ['nullable', 'string', 'max:255'],
            'catalog_item_id' => ['nullable', Rule::exists('catalog_items', 'id')],
            'service_slug' => ['nullable', 'string'],
        ]);

        $resolvedCity = null;
        if (! empty($validated['city_slug'])) {
            $resolvedCity = City::where('slug', $validated['city_slug'])->first();
            if (! $resolvedCity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Wilayah kota/kabupaten tidak valid.',
                ], 422);
            }
        } elseif (! empty($validated['city_id'])) {
            $resolvedCity = City::find($validated['city_id']);
        }

        $resolvedKecamatan = null;
        if (! empty($validated['district_slug']) && $resolvedCity) {
            $resolvedKecamatan = Kecamatan::where('slug', $validated['district_slug'])
                ->where('city_id', $resolvedCity->id)
                ->first();
        } elseif (! empty($validated['kecamatan_id'])) {
            $resolvedKecamatan = Kecamatan::find($validated['kecamatan_id']);
        }

        $resolvedItem = null;
        if (! empty($validated['catalog_item_id'])) {
            $resolvedItem = CatalogItem::find($validated['catalog_item_id']);
        } elseif (! empty($validated['service_slug'])) {
            $resolvedItem = CatalogItem::where('slug', $validated['service_slug'])->first();
        }

        $params = [
            'topic' => $validated['topic'] ?? null,
            'word_count' => $validated['word_count'],
            'catalog_item_id' => $resolvedItem?->id,
            'service_slug' => $resolvedItem?->slug,
            'city_id' => $resolvedCity?->id,
            'city_name' => $resolvedCity?->name ?? 'Indonesia',
            'city_slug' => $resolvedCity?->slug,
            'kecamatan_id' => $resolvedKecamatan?->id,
            'district_name' => $resolvedKecamatan?->name,
            'district_slug' => $resolvedKecamatan?->slug,
        ];

        $result = $this->ollama->generateArticle($params);

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Gagal menghasilkan artikel dari Ollama.',
            ], 500);
        }

        return response()->json($result);
    }

    /**
     * Chunked preview generation endpoint using ArticleGeneratorService.
     * Backwards-compatible with Varilevke's ArticleGeneratePreviewTest.
     */
    public function generatePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'min:5', 'max:300'],
            'word_count' => ['nullable', 'integer', 'min:8000', 'max:10000'],
            'catalog_item_id' => ['nullable', Rule::exists('catalog_items', 'id')],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'kecamatan_id' => [
                'nullable',
                Rule::exists('kecamatans', 'id'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateKecamatanTarget($request, $value, $fail),
            ],
        ]);

        try {
            $draft = $this->generator->generate(new GenerationRequest(
                topic: $validated['topic'],
                catalogItem: isset($validated['catalog_item_id']) ? CatalogItem::find($validated['catalog_item_id']) : null,
                city: isset($validated['city_id']) ? City::find($validated['city_id']) : null,
                kecamatan: isset($validated['kecamatan_id']) ? Kecamatan::find($validated['kecamatan_id']) : null,
                wordCount: $validated['word_count'] ?? null,
            ));
        } catch (GenerationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Generation failed unexpectedly. Please retry.'], 500);
        }

        return response()->json(['draft' => $draft->toArray()]);
    }

    /**
     * Handle image upload for Jodit editor or standalone featured image.
     */
    public function uploadImage(Request $request, ImageUploadService $imageUploader): JsonResponse
    {
        $file = $request->file('image')
            ?? $request->file('file')
            ?? ($request->file('files')[0] ?? null);

        if (! $file) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada file gambar yang diunggah.',
            ], 400);
        }

        $result = $imageUploader->storeImage($file);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'url' => $result['url'],
            'path' => $result['path'],
            'data' => [
                'url' => $result['url'],
                'path' => $result['path'],
            ],
            'files' => [$result['url']],
        ]);
    }

    /**
     * Preprocess action buttons and JSON input fields.
     */
    private function preprocessActionsAndJson(Request $request): void
    {
        if ($request->filled('action')) {
            $action = strtolower(trim((string) $request->input('action')));
            if (in_array($action, ['publish', 'published'])) {
                $request->merge(['status' => Article::STATUS_PUBLISHED]);
            } elseif (in_array($action, ['draft', 'save_draft'])) {
                $request->merge(['status' => Article::STATUS_DRAFT]);
            }
        }

        // Map target_city_slug to city_id if city_id is not already set
        if (! $request->filled('city_id') && $request->filled('target_city_slug')) {
            $city = City::where('slug', $request->input('target_city_slug'))->first();
            if ($city) {
                $request->merge(['city_id' => $city->id]);
            }
        }

        // Map target_district_slug to kecamatan_id if missing
        if (! $request->filled('kecamatan_id') && $request->filled('target_district_slug')) {
            $kecamatan = Kecamatan::where('slug', $request->input('target_district_slug'))->first();
            if ($kecamatan) {
                $request->merge(['kecamatan_id' => $kecamatan->id]);
            }
        }

        // Decode image_brief if submitted as JSON string
        if ($request->has('image_brief')) {
            $brief = $request->input('image_brief');
            if (is_string($brief)) {
                $brief = trim($brief);
                $decoded = $brief !== '' ? json_decode($brief, true) : null;
                $request->merge(['image_brief' => (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null]);
            }
        }

        // Decode faqs if submitted as JSON string
        if ($request->has('faqs')) {
            $faqs = $request->input('faqs');
            if (is_string($faqs)) {
                $faqs = trim($faqs);
                $decoded = $faqs !== '' ? json_decode($faqs, true) : null;
                $request->merge(['faqs' => (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null]);
            }
        }
    }

    /**
     * Validate article data for store/update.
     */
    private function validateArticleData(Request $request, ?Article $existing): array
    {
        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            $existing !== null
                ? Rule::unique('articles', 'slug')->ignore($existing->id)
                : Rule::unique('articles', 'slug'),
        ];

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => $slugRules,
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'catalog_item_id' => ['nullable', Rule::exists('catalog_items', 'id')],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'kecamatan_id' => [
                'nullable',
                Rule::exists('kecamatans', 'id'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateKecamatanTarget($request, $value, $fail),
            ],
            'featured_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'image_brief' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
        ]);

        // If paired textarea inputs are supplied for FAQs (faq_questions, faq_answers)
        if ($request->filled('faq_questions') && $request->filled('faq_answers')) {
            $questions = array_values(array_filter(
                $this->textareaLines($request->input('faq_questions')),
                fn (string $line): bool => trim($line) !== '' && trim($line) !== '0'
            ));
            $answers = array_values(array_filter(
                $this->textareaLines($request->input('faq_answers')),
                fn (string $line): bool => trim($line) !== '' && trim($line) !== '0'
            ));

            $data['faqs'] = collect($questions)
                ->take(10)
                ->map(fn (?string $q, int $idx): array => [
                    'question' => trim((string) $q),
                    'answer' => trim((string) ($answers[$idx] ?? '')),
                ])
                ->filter(fn (array $faq): bool => $faq['question'] !== '' && $faq['answer'] !== '')
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * Compute reading time, word count, and published timestamp.
     */
    private function withComputedFields(array $data, ?Article $existing = null): array
    {
        $wordCount = str_word_count(strip_tags((string) $data['content']));
        $data['word_count'] = $wordCount;
        $data['reading_time'] = (int) max(1, ceil($wordCount / 200));

        if ($data['status'] === Article::STATUS_PUBLISHED) {
            $data['published_at'] = $existing?->published_at ?? now();
        } else {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function validateKecamatanTarget(Request $request, mixed $value, \Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $cityId = $request->input('city_id');
        if (empty($cityId) || ! Kecamatan::query()->whereKey($value)->where('city_id', $cityId)->exists()) {
            $fail('Kecamatan harus menjadi milik kota yang sama dengan pilihan kota.');
        }
    }

    private function textareaLines(mixed $input): array
    {
        if (is_array($input)) {
            return array_map('strval', $input);
        }

        if (! is_string($input) || $input === '') {
            return [];
        }

        return preg_split('/\r\n|\r|\n/', $input) ?: [];
    }

    /**
     * Form data shared by create and edit views.
     */
    private function formData(): array
    {
        $cities = City::query()->orderBy('name')->get(['id', 'name', 'slug', 'province', 'classification']);
        $provinces = $cities->pluck('province')->filter()->unique()->sort()->values();

        return [
            'catalogItems' => CatalogItem::query()->orderBy('category')->orderBy('name')->get(['id', 'name', 'category']),
            'cities' => $cities,
            'provinces' => $provinces,
            'aiEnabled' => (bool) (config('services.ollama.base_url') ?: config('services.ai.enabled')),
        ];
    }
}
