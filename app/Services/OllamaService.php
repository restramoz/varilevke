<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\Ollama\ArticlePromptBuilder;
use App\Support\WhatsApp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OllamaService
{
    protected ?string $apiKey;

    protected ?string $baseUrl;

    protected ?string $model;

    protected int $timeout;

    protected int $maxAttempts;

    public function __construct(
        protected ?HtmlSanitizerService $sanitizer = null,
        protected ?ArticlePromptBuilder $promptBuilder = null,
    ) {
        $this->sanitizer = $sanitizer ?? new HtmlSanitizerService;
        $this->promptBuilder = $promptBuilder ?? new ArticlePromptBuilder;

        $this->baseUrl = config('services.ollama.base_url') ?: config('services.ai.base_url');
        $this->model = config('services.ollama.model') ?: config('services.ai.model');
        $this->apiKey = config('services.ollama.key') ?: config('services.ai.api_key');
        $this->timeout = (int) (config('services.ollama.timeout') ?: config('services.ai.timeout', 120));
        $this->maxAttempts = (int) (config('services.ollama.max_attempts') ?: config('services.ai.max_attempts', 3));
    }

    /**
     * Resolve chat endpoint URL safely.
     */
    public function getChatEndpoint(): string
    {
        $base = rtrim((string) $this->baseUrl, '/');

        if (str_ends_with($base, '/api/chat') || str_ends_with($base, '/chat/completions')) {
            return $base;
        }

        if (str_ends_with($base, '/v1')) {
            return "{$base}/chat/completions";
        }

        return "{$base}/api/chat";
    }

    /**
     * Determine if current endpoint uses OpenAI-compatible format.
     */
    public function isOpenAiCompatible(): bool
    {
        $endpoint = $this->getChatEndpoint();

        return str_contains($endpoint, '/chat/completions');
    }

    /**
     * Resolve word count within strict Varilevke boundaries (6,000–10,000 words, default 8,000).
     */
    public function resolveWordCount(int $wordCount): int
    {
        return max(6000, min(10000, $wordCount));
    }

    /**
     * Pick a relevant topic automatically if none was provided by the user.
     */
    public function pickTopic(?CatalogItem $catalogItem, ?City $city): string
    {
        $location = $city !== null ? " di {$city->label()}" : ' di Indonesia';

        if ($catalogItem !== null) {
            if ($catalogItem->category === 'jasa') {
                return "Panduan Lengkap {$catalogItem->name}{$location}: Ruang Lingkup, Prosedur, dan Mitigasi Risiko";
            }

            return "Panduan Komprehensif Pelatihan {$catalogItem->name}{$location}: Kompetensi, Kurikulum, dan Praktik Keselamatan Kerja";
        }

        return "Panduan Manajemen Keselamatan dan Kesehatan Kerja (K3) Industri{$location}";
    }

    /**
     * Generate an article draft via Ollama.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateArticle(array $params): array
    {
        // 1. Validate configuration
        if (empty($this->baseUrl)) {
            return [
                'success' => false,
                'error' => 'Konfigurasi OLLAMA_BASE_URL belum diatur di file .env atau konfigurasi.',
            ];
        }

        if (empty($this->model)) {
            return [
                'success' => false,
                'error' => 'Konfigurasi OLLAMA_MODEL belum diatur di file .env atau konfigurasi.',
            ];
        }

        // 2. Resolve parameters & relationships
        $wordCount = $this->resolveWordCount((int) ($params['word_count'] ?? 8000));

        // Catalog item
        $catalogItem = null;
        if (! empty($params['catalog_item_id'])) {
            $catalogItem = CatalogItem::find($params['catalog_item_id']);
        } elseif (! empty($params['service_slug'])) {
            $catalogItem = CatalogItem::where('slug', $params['service_slug'])->first();
        }

        // City
        $city = null;
        if (! empty($params['city_id'])) {
            $city = City::find($params['city_id']);
        } elseif (! empty($params['city_slug'])) {
            $city = City::where('slug', $params['city_slug'])->first();
        }

        // City name fallback if unmapped string given
        $cityName = $city?->name ?? (string) ($params['city_name'] ?? '');

        // Kecamatan (with verified parent-child validation)
        $kecamatan = null;
        if (! empty($params['kecamatan_id'])) {
            $kecamatan = Kecamatan::find($params['kecamatan_id']);
            if ($kecamatan && $city && $kecamatan->city_id !== $city->id) {
                return [
                    'success' => false,
                    'error' => 'Kecamatan yang dipilih tidak valid untuk kota/kabupaten terkait.',
                ];
            }
        } elseif (! empty($params['district_slug']) && $city) {
            $kecamatan = Kecamatan::where('slug', $params['district_slug'])
                ->where('city_id', $city->id)
                ->first();
        }

        // Topic
        $topic = ! empty($params['topic'])
            ? trim((string) $params['topic'])
            : $this->pickTopic($catalogItem, $city);

        // 3. Build prompts via prompt system
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($catalogItem, $city, $kecamatan, $wordCount);
        $userPrompt = $this->promptBuilder->buildUserPrompt($topic, $catalogItem, $city, $kecamatan, $wordCount);

        // 4. Send chat request with bounded retries on transient errors
        $responsePayload = $this->sendChatWithRetry([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        if (! $responsePayload['success']) {
            return $responsePayload;
        }

        $rawContent = trim((string) ($responsePayload['content'] ?? ''));
        if ($rawContent === '') {
            return [
                'success' => false,
                'error' => 'Ollama mengembalikan respons konten kosong.',
            ];
        }

        // 5. Parse response strictly
        $parseResult = $this->parseResponse($rawContent, $topic, $cityName, $catalogItem);
        if (! $parseResult['success']) {
            return $parseResult;
        }

        // 6. Word-count enforcement & bounded continuation loop
        $currentWords = $this->countWords($parseResult['content']);

        if ($currentWords > 10000) {
            return [
                'success' => false,
                'error' => 'Artikel yang dihasilkan melebihi batas maksimum 10.000 kata.',
            ];
        }

        // Bounded continuation loop: max 6 attempts to reach minimum 6,000 words
        $maxAttempts = 6;
        $attempt = 0;
        while ($currentWords < 6000 && $attempt < $maxAttempts) {
            $remaining = max(6000 - $currentWords, 500);
            $continuation = $this->requestContinuation($parseResult['content'], $remaining, $catalogItem, $city, $kecamatan);

            if (! $continuation['success']) {
                return $continuation;
            }

            $parseResult['content'] .= "\n".$continuation['content'];
            $currentWords = $this->countWords($parseResult['content']);
            $attempt++;
        }

        if ($currentWords < 6000) {
            return [
                'success' => false,
                'error' => "Artikel yang dihasilkan ({$currentWords} kata) kurang dari batas minimum 6.000 kata.",
            ];
        }

        if ($currentWords > 10000) {
            $parseResult['content'] = $this->trimToWordLimit($parseResult['content'], 10000);
            $currentWords = $this->countWords($parseResult['content']);
        }

        // 7. Inject 5 WhatsApp CTA callouts
        $parseResult = $this->addWhatsAppCallout($parseResult, $cityName, $catalogItem?->name);

        // 8. Associate resolved IDs
        $parseResult['catalog_item_id'] = $catalogItem?->id;
        $parseResult['city_id'] = $city?->id;
        $parseResult['kecamatan_id'] = $kecamatan?->id;
        $parseResult['word_count'] = $currentWords;
        $parseResult['reading_time'] = (int) max(1, ceil($currentWords / 200));

        return $parseResult;
    }

    /**
     * Send chat request with bounded retries for transient HTTP failures.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{success: bool, content?: string, error?: string}
     */
    protected function sendChatWithRetry(array $messages): array
    {
        $endpoint = $this->getChatEndpoint();
        $isOpenAi = $this->isOpenAiCompatible();
        $attempts = max(1, min(5, $this->maxAttempts));
        $lastError = 'unknown error';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $client = Http::timeout($this->timeout);

                if (! empty($this->apiKey)) {
                    $client = $client->withToken($this->apiKey);
                }

                $payload = [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ];

                if ($isOpenAi) {
                    $payload['temperature'] = 0.4;
                } else {
                    $payload['options'] = ['temperature' => 0.4];
                }

                $response = $client->post($endpoint, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $isOpenAi
                        ? ($data['choices'][0]['message']['content'] ?? null)
                        : ($data['message']['content'] ?? ($data['choices'][0]['message']['content'] ?? null));

                    if (is_string($content) && trim($content) !== '') {
                        return [
                            'success' => true,
                            'content' => $content,
                        ];
                    }

                    $lastError = 'Ollama mengembalikan respons dengan konten pesan kosong.';
                } else {
                    $status = $response->status();

                    // Do not retry 4xx client errors (401, 403, 404, 422)
                    if ($status >= 400 && $status < 500) {
                        return [
                            'success' => false,
                            'error' => "Ollama merespons dengan status HTTP {$status}: ".Str::limit($response->body(), 200),
                        ];
                    }

                    $lastError = "Ollama merespons dengan status HTTP {$status}: ".Str::limit($response->body(), 200);
                }
            } catch (ConnectionException $e) {
                Log::warning("Ollama connection exception on attempt {$attempt}: ".$e->getMessage());
                $lastError = 'Koneksi ke Ollama gagal atau timeout setelah '.$this->timeout.' detik. Pastikan layanan Ollama aktif dan endpoint dapat dijangkau.';
            } catch (\Throwable $e) {
                Log::warning("Ollama exception on attempt {$attempt}: ".$e->getMessage());
                $lastError = 'Terjadi kesalahan saat memproses permintaan: '.$e->getMessage();
            }
        }

        return [
            'success' => false,
            'error' => $lastError,
        ];
    }

    /**
     * Strict JSON parsing and extraction. Fails explicitly if output is invalid.
     *
     * @return array<string, mixed>
     */
    public function parseResponse(string $rawContent, string $fallbackTopic, string $cityName, ?CatalogItem $catalogItem = null): array
    {
        $brandLine = $catalogItem?->name ?? 'Varilevke';
        $cleaned = trim($rawContent);

        // Strip markdown code fences if present
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $cleaned, $matches)) {
            $cleaned = trim($matches[1]);
        } elseif (str_starts_with($cleaned, '```json')) {
            $cleaned = trim(substr($cleaned, 7));
            if (str_ends_with($cleaned, '```')) {
                $cleaned = trim(substr($cleaned, 0, -3));
            }
        } elseif (str_starts_with($cleaned, '```')) {
            $cleaned = trim(substr($cleaned, 3));
            if (str_ends_with($cleaned, '```')) {
                $cleaned = trim(substr($cleaned, 0, -3));
            }
        }

        $cleaned = preg_replace('/```(?:json)?/i', '', $cleaned) ?? $cleaned;
        $cleaned = str_replace('```', '', $cleaned);
        $cleaned = trim($cleaned);

        // Repair invalid JSON escape sequences (LaTeX, unescaped quotes)
        $cleaned = $this->repairJsonEscapes($cleaned);

        $decoded = json_decode($cleaned, true);

        // Extract first balanced JSON object if direct decode failed
        if (! is_array($decoded)) {
            $json = $this->extractJsonObject($cleaned);
            if ($json !== null) {
                $decoded = json_decode($json, true);
            }
        }

        // Retry after removing trailing commas
        if (! is_array($decoded)) {
            $repaired = preg_replace('/,\s*([}\]])/', '$1', $cleaned) ?? $cleaned;
            $decoded = json_decode($repaired, true);
            if (! is_array($decoded)) {
                $json = $this->extractJsonObject($repaired);
                if ($json !== null) {
                    $decoded = json_decode($json, true);
                }
            }
        }

        // Validate decoded structure strictly
        if (is_array($decoded) && ! empty($decoded['title'])) {
            $imageBrief = $decoded['image_brief'] ?? [];
            if (! is_array($imageBrief)) {
                $imageBrief = [];
            }

            $locationContext = ! empty($cityName) ? " di {$cityName}" : '';
            $concept = $imageBrief['concept'] ?? "Penerapan kompetensi {$brandLine}{$locationContext}";
            $englishPrompt = $imageBrief['english_prompt'] ?? 'Professional documentary photograph of modern workplace safety in Indonesia, technicians with protective equipment performing safe inspection, industrial lighting, realistic, no text overlays';
            $aspectRatio = $imageBrief['aspect_ratio'] ?? '16:9';
            $recommendedAlt = $imageBrief['recommended_alt'] ?? "{$brandLine}{$locationContext}";

            $rawArticleContent = $this->cleanLatexSymbols((string) ($decoded['content'] ?? ''));
            $sanitizedContent = $this->sanitizer->sanitize($rawArticleContent);

            return [
                'success' => true,
                'title' => (string) $decoded['title'],
                'slug' => ! empty($decoded['slug']) ? Str::slug($decoded['slug']) : Str::slug($decoded['title']),
                'excerpt' => (string) ($decoded['excerpt'] ?? Str::limit(strip_tags($sanitizedContent), 180)),
                'content' => $sanitizedContent,
                'seo_title' => (string) ($decoded['seo_title'] ?? Str::limit($decoded['title'].' | Varilevke', 60)),
                'meta_description' => (string) ($decoded['meta_description'] ?? Str::limit($decoded['excerpt'] ?? '', 155)),
                'suggested_faqs' => is_array($decoded['suggested_faqs'] ?? null) ? $decoded['suggested_faqs'] : [],
                'image_brief' => [
                    'concept' => $concept,
                    'english_prompt' => $englishPrompt,
                    'aspect_ratio' => $aspectRatio,
                    'recommended_alt' => $recommendedAlt,
                ],
            ];
        }

        // If JSON cannot be parsed into structured format, fail explicitly
        // Never return success with raw malformed model text!
        // Check if there is recoverable "content" inside
        if (preg_match('/"content"\s*:\s*("(?:\\\\.|[^"\\\\])*")/s', $cleaned, $match)) {
            $extracted = json_decode($match[1], true);
            if (is_string($extracted) && trim($extracted) !== '') {
                $sanitized = $this->sanitizer->sanitize($this->cleanLatexSymbols($extracted));

                return [
                    'success' => true,
                    'title' => $fallbackTopic,
                    'slug' => Str::slug($fallbackTopic),
                    'excerpt' => Str::limit(strip_tags($sanitized), 180),
                    'content' => $sanitized,
                    'seo_title' => Str::limit("{$fallbackTopic} | Varilevke", 60),
                    'meta_description' => Str::limit("Panduan {$fallbackTopic}.", 155),
                    'suggested_faqs' => [],
                    'image_brief' => [
                        'concept' => "Penerapan {$brandLine}",
                        'english_prompt' => 'Professional documentary photograph of workplace safety inspection in Indonesia, realistic lighting, no text',
                        'aspect_ratio' => '16:9',
                        'recommended_alt' => "{$brandLine} - {$fallbackTopic}",
                    ],
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Ollama mengembalikan format JSON yang tidak valid atau struktur artikel tidak lengkap. Draf tidak dapat disimpan.',
        ];
    }

    /**
     * Request a continuation chunk from Ollama for partial content.
     *
     * @return array{success: bool, content?: string, error?: string}
     */
    protected function requestContinuation(
        string $existingContent,
        int $targetWords,
        ?CatalogItem $catalogItem,
        ?City $city,
        ?Kecamatan $kecamatan
    ): array {
        $continuationPrompts = $this->promptBuilder->buildContinuationPrompt(
            $existingContent,
            $targetWords,
            $catalogItem,
            $city,
            $kecamatan
        );

        $responsePayload = $this->sendChatWithRetry([
            ['role' => 'system', 'content' => $continuationPrompts['system']],
            ['role' => 'user', 'content' => $continuationPrompts['user']],
        ]);

        if (! $responsePayload['success']) {
            return $responsePayload;
        }

        $raw = trim((string) ($responsePayload['content'] ?? ''));
        if ($raw === '') {
            return ['success' => false, 'error' => 'Continuation response was empty'];
        }

        // Clean fences if present
        $clean = preg_replace('/^```(?:html)?\s*(.*?)\s*```$/s', '$1', $raw) ?? $raw;
        $cleanedHtml = $this->sanitizer->sanitize($this->cleanLatexSymbols($clean));
        $addedWords = $this->countWords($cleanedHtml);

        if ($addedWords === 0) {
            return ['success' => false, 'error' => 'Continuation fragment contained no usable words'];
        }

        // Duplicate check against existing content
        $normExisting = preg_replace('/\s+/u', ' ', strip_tags($existingContent)) ?? '';
        $normContinuation = preg_replace('/\s+/u', ' ', strip_tags($cleanedHtml)) ?? '';

        if (trim($normContinuation) !== '' && str_contains($normExisting, trim($normContinuation))) {
            return ['success' => false, 'error' => 'Continuation repeated existing content'];
        }

        return [
            'success' => true,
            'content' => $cleanedHtml,
        ];
    }

    /**
     * Count words in HTML content while ignoring tags and decoding entities.
     */
    public function countWords(string $content): int
    {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $words = preg_split('/\s+/u', trim($text));

        return $words === false ? 0 : count(array_filter($words, fn ($w) => $w !== ''));
    }

    /**
     * Trim HTML content to word limit at whole block elements.
     */
    protected function trimToWordLimit(string $html, int $maxWords): string
    {
        $blocks = [];
        if (preg_match_all('/<(p|h[2-6]|ul|ol|table|blockquote)[^>]*>.*?<\/\1>/is', $html, $matches)) {
            $blocks = $matches[0];
        }

        if (empty($blocks)) {
            return $html;
        }

        $out = '';
        $count = 0;

        foreach ($blocks as $block) {
            $blockWords = $this->countWords($block);
            if ($count + $blockWords > $maxWords) {
                break;
            }
            $out .= $block."\n";
            $count += $blockWords;
        }

        return trim($out) ?: $html;
    }

    /**
     * Add 5 context-aware WhatsApp CTA callouts distributed across `<p>` tags.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function addWhatsAppCallout(array $result, string $cityName, ?string $serviceName = null): array
    {
        $content = $result['content'] ?? '';
        $brandLine = $serviceName ?: 'Layanan & Pelatihan K3 Varilevke';
        $locationLabel = ! empty($cityName) ? "Wilayah {$cityName}" : 'Indonesia';

        $contacts = WhatsApp::numbers();
        if (empty($contacts)) {
            return $result;
        }

        $totalContacts = count($contacts);
        $selected = [];
        for ($i = 0; $i < 5; $i++) {
            $selected[] = $contacts[$i % $totalContacts];
        }

        $paragraphEnds = [];
        $offset = 0;
        while (($pos = strpos($content, '</p>', $offset)) !== false) {
            $paragraphEnds[] = $pos + 4;
            $offset = $pos + 4;
        }

        $insertPoints = [];
        $countEnds = count($paragraphEnds);
        for ($i = 0; $i < 5; $i++) {
            if ($countEnds === 0) {
                $insertPoints[] = null;

                continue;
            }
            $target = (int) round(($i + 1) * $countEnds / 6);
            $insertPoints[] = $paragraphEnds[$target] ?? end($paragraphEnds);
        }

        foreach (array_reverse($insertPoints, true) as $idx => $position) {
            $phone = $selected[$idx];
            $message = "Halo, saya ingin konsultasi mengenai {$brandLine} untuk {$locationLabel}.";
            $waUrl = WhatsApp::link($phone, $message);

            $ctaHtml = '<div class="my-6 p-4 rounded-xl bg-regulatory-800 border border-teal-500/40 flex flex-col sm:flex-row items-center justify-between gap-4 not-prose">'
                .'<div>'
                .'<div class="text-xs font-bold uppercase tracking-wider text-accent">Konsultasi '.$brandLine.' '.$locationLabel.'</div>'
                .'<div class="text-sm font-semibold text-regulatory-100">Butuh informasi lengkap atau penawaran resmi?</div>'
                .'</div>'
                .'<a href="'.$waUrl.'" target="_blank" rel="noopener noreferrer" class="btn-primary text-xs shrink-0 inline-flex items-center gap-2">'
                .'<span>Konsultasi WhatsApp</span> &rarr;'
                .'</a>'
                .'</div>';

            if ($position !== null) {
                $content = substr_replace($content, $ctaHtml, $position, 0);
            } else {
                $content .= $ctaHtml;
            }
        }

        $result['content'] = $content;

        return $result;
    }

    /**
     * Repair invalid JSON escape sequences produced by LaTeX/backslashes.
     */
    protected function repairJsonEscapes(string $content): string
    {
        $content = preg_replace_callback(
            '~\\\\[A-Za-z]{2,}~',
            static fn ($m) => '\\\\'.substr($m[0], 1),
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '~\\\\(?:u[0-9a-fA-F]{4}|.)~s',
            static function ($m) {
                $esc = $m[0];
                if ($esc === '\\"' || $esc === '\\\\' || $esc === '\\/' ||
                    $esc === '\\b' || $esc === '\\f' || $esc === '\\n' ||
                    $esc === '\\r' || $esc === '\\t') {
                    return $esc;
                }
                if (preg_match('~^\\\\u[0-9a-fA-F]{4}$~', $esc)) {
                    return $esc;
                }

                return '\\\\'.substr($esc, 1);
            },
            $content
        ) ?? $content;

        return $content;
    }

    /**
     * Extract the first balanced JSON object from a string.
     */
    protected function extractJsonObject(string $content): ?string
    {
        $start = strpos($content, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($content);

        for ($i = $start; $i < $len; $i++) {
            $ch = $content[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($ch === '\\') {
                    $escape = true;
                } elseif ($ch === '"') {
                    $inString = false;
                }
            } else {
                if ($ch === '"') {
                    $inString = true;
                } elseif ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        return substr($content, $start, $i - $start + 1);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Clean LaTeX symbols from content so HTML remains clean and readable.
     */
    protected function cleanLatexSymbols(string $content): string
    {
        $content = preg_replace('/\\\\\[[\s\S]*?\\\\\]/u', '', $content) ?? $content;
        $content = preg_replace('/\$\$[\s\S]*?\$\$/u', '', $content) ?? $content;
        $content = preg_replace('/\\\\begin\{[^}]+\}[\s\S]*?\\\\end\{[^}]+\}/u', '', $content) ?? $content;

        $content = preg_replace_callback(
            '/\\\\(?:text|textbf|textit|emph|mathbf|mathrm)\{([^}]*)\}/u',
            fn ($m) => $m[1],
            $content
        ) ?? $content;

        $content = str_replace(
            ['\\rightarrow', '\\leftarrow', '\\Rightarrow', '\\Leftarrow', '\\leftrightarrow',
                '\\times', '\\cdot', '\\pm', '\\leq', '\\geq', '\\neq', '\\approx',
                '\\alpha', '\\beta', '\\gamma', '\\delta', '\\Delta', '\\sigma',
                '\\mu', '\\lambda', '\\pi', '\\theta', '\\phi', '\\Sigma'],
            ['→', '←', '⇒', '⇐', '↔',
                '×', '·', '±', '≤', '≥', '≠', '≈',
                'α', 'β', 'γ', 'δ', 'Δ', 'σ',
                'μ', 'λ', 'π', 'θ', 'φ', 'Σ'],
            $content
        );

        $content = preg_replace('/\\\\[a-zA-Z]+\b/u', '', $content) ?? $content;
        $content = str_replace(['$', '{', '}'], '', $content);

        return preg_replace('/[ \t]{2,}/', ' ', $content) ?? $content;
    }
}
