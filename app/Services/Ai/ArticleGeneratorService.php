<?php

namespace App\Services\Ai;

use App\Services\HtmlSanitizerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Chunked long-article generation. Never persists anything — it only returns
 * a draft for the admin preview/edit flow. The server measures the word
 * count itself; a claimed count is never trusted.
 */
final class ArticleGeneratorService
{
    public const MIN_WORDS = 8000;

    public const MAX_WORDS = 10000;

    public const DEFAULT_WORDS = 8000;

    /** Chunks stay deliberately small relative to the target word budget. */
    private const SECTION_COUNT = 8;

    /** Extra words requested per chunk so slowdowns still clear the floor. */
    private const CHUNK_OVERHEAD = 1.08;

    public function __construct(
        private readonly AiClient $client,
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * @throws GenerationException
     */
    public function generate(GenerationRequest $request): GeneratedArticle
    {
        $target = max(self::MIN_WORDS, min(self::MAX_WORDS, $request->targetWords()));

        $outline = $this->buildOutline($request, $target);
        $chunks = $this->generateSections($request, $outline, $target);
        $content = $this->assemble($chunks);

        $wordCount = str_word_count(strip_tags($content));

        if ($wordCount < self::MIN_WORDS) {
            throw new GenerationException(
                "Generated article is too short: {$wordCount} words (minimum ".self::MIN_WORDS.'). Nothing was saved.'
            );
        }

        if ($wordCount > self::MAX_WORDS) {
            $content = $this->trimWithinSectionBoundaries($content, self::MAX_WORDS);
        }

        return new GeneratedArticle(
            title: $outline['title'],
            excerpt: $this->buildExcerpt($outline['summary']),
            html: $content,
            faqs: $outline['faqs'],
            wordCount: str_word_count(strip_tags($content)),
        );
    }

    /**
     * @return array{title: string, summary: string, sections: list<array{heading: string, brief: string, words: int}>, faqs: list<array{question: string, answer: string}>}
     */
    private function buildOutline(GenerationRequest $request, int $target): array
    {
        $json = $this->client->chat([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->outlinePrompt($request, $target)],
        ], jsonMode: true);

        return $this->decodeJson($json, [
            'title' => 'required|string|min:3|max:120',
            'summary' => 'required|string|min:50|max:1000',
            'sections' => 'required|array|min:'.self::SECTION_COUNT.'|max:'.(self::SECTION_COUNT + 2),
            'sections.*.heading' => 'required|string|min:3|max:120',
            'sections.*.brief' => 'required|string|max:500',
            'sections.*.words' => 'required|integer|min:200|max:1500',
            'faqs' => 'required|array|min:2|max:8',
            'faqs.*.question' => 'required|string|min:3|max:200',
            'faqs.*.answer' => 'required|string|min:3|max:500',
        ]);
    }

    /**
     * @param  array{sections: list<array{heading: string, brief: string, words: int}>}  $outline
     * @return list<string> sanitized HTML fragments per section
     */
    private function generateSections(GenerationRequest $request, array $outline, int $target): array
    {
        $sections = $outline['sections'];
        $perSection = (int) max(500, ceil($target * self::CHUNK_OVERHEAD / max(count($sections), 1)));
        $chunks = [];
        $region = $request->regionLabel();

        foreach ($sections as $index => $section) {
            $words = (int) round(max($perSection, (int) $section['words']));

            $fragment = $this->client->chat([
                ['role' => 'system', 'content' => $this->sectionSystemPrompt()],
                ['role' => 'user', 'content' => $this->sectionPrompt($section, $words, $region)],
            ]);

            $chunks[$index] = [
                'heading' => $section['heading'],
                'html' => $this->decodeSectionHtml($fragment, $section['heading']),
                'requested_words' => $words,
            ];
        }

        return $chunks;
    }

    /**
     * Sections are JSON-wrapped ({html: "..."}) so chunk boundaries stay
     * machine-checkable. Invalid or empty output fails loudly.
     *
     * @return string sanitized html fragment
     */
    private function decodeSectionHtml(string $raw, string $heading): string
    {
        $decoded = $this->decodeJson($raw, ['html' => 'required|string|min:50|max:200000', 'heading' => 'nullable|string|max:120']);

        $html = trim((string) $decoded['html']);

        if ($html === '') {
            throw new GenerationException("Section \"{$heading}\" produced empty output.");
        }

        // Sanitize at the generation boundary too, so previews are as safe as
        // the persisted article will be.
        return $this->sanitizer->sanitize($html);
    }

    /**
     * @param  array<string, string>  $rules
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw, array $rules): array
    {
        $clean = trim($raw);

        // Some providers still wrap JSON in markdown fences despite json mode.
        if (preg_match('/^```(?:json)?\s*(.+?)\s*```$/s', $clean, $matched)) {
            $clean = trim($matched[1]);
        }

        try {
            $data = json_decode($clean, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new GenerationException('AI returned invalid JSON. Nothing was saved.');
        }

        if (! is_array($data)) {
            throw new GenerationException('AI returned an unexpected JSON shape. Nothing was saved.');
        }

        $check = Validator::make($data, $rules);

        if ($check->fails()) {
            throw new GenerationException('AI JSON failed field validation: '.implode('; ', $check->errors()->all()));
        }

        return $data;
    }

    /**
     * @param  list<array{heading: string, html: string, requested_words: int}>  $chunks
     */
    private function assemble(array $chunks): string
    {
        $html = '';

        foreach ($chunks as $chunk) {
            $html .= '<h2>'.e($chunk['heading']).'</h2>'."\n".$chunk['html']."\n";
        }

        return $html;
    }

    /**
     * Trim only whole trailing block elements so a cut never eats a table
     * middle; measure the actual result, never the original claim.
     */
    private function trimWithinSectionBoundaries(string $html, int $maxWords): string
    {
        $blocks = [];

        if (preg_match_all('/<(p|h[2-6]|ul|ol|table|blockquote)[^>]*>.*?<\/\1>/is', $html, $matches)) {
            $blocks = $matches[0];
        }

        $out = '';
        $count = 0;

        foreach ($blocks as $block) {
            $blockWords = str_word_count(strip_tags($block));

            if ($count + $blockWords > $maxWords) {
                break;
            }

            $out .= $block;
            $count += $blockWords;
        }

        return $out;
    }

    /**
     * @param  string|mixed  $summary
     */
    private function buildExcerpt(mixed $summary): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $summary))), 280);
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'You write in Indonesian only, producing valid JSON.',
            'Base every regional statement strictly on the region data given (name/province).',
            'Never invent statistics, certifications, addresses, phone numbers or office locations.',
            'Marketing tone is allowed, but factual claims must be generic or clearly conditional.',
        ]);
    }

    private function outlinePrompt(GenerationRequest $request, int $target): string
    {
        $line = 'Tulis kerangka artikel:'.PHP_EOL
            .'Tema: '.trim((string) $request->topic).PHP_EOL
            .'Kata target: sekitar '.$target.' kata'.PHP_EOL
            .'Jumlah bagian: tengatap '.self::SECTION_COUNT.' bagian'.PHP_EOL;

        if (($region = $request->regionLabel()) !== null) {
            $line .= 'Wilayah konteks (jangan mengklaim alamat atau statistik spesifik): '.$region.PHP_EOL;
        }

        if (($item = $request->catalogItem) !== null) {
            $line .= 'Layanan terkait: '.$item->name.' (kategori '.$item->category.').'.PHP_EOL;
        }

        $line .= 'Format JSON: {"title": string, "summary": string, "sections": [{"heading": string, "brief": string, "words": integer}], "faqs": [{"question": string, "answer": string}]}';

        return $line;
    }

    private function sectionSystemPrompt(): string
    {
        return implode("\n", [
            'You write one article section in Indonesian, valid JSON only.',
            'HTML tags allowed: p, h3, ul, ol, li, strong, em, table/thead/tbody/tr/td/th.',
            'Tables must use thead+tbody and stay limited to 5 columns.',
            'Never use script, style, iframe, img or external links.',
            'Do not invent facts, statistics, prices or certifications.',
        ]);
    }

    /**
     * @param  array{heading: string, brief: string, words: int}  $section
     */
    private function sectionPrompt(array $section, int $words, ?string $region): string
    {
        $line = 'Tulis bagian artikel dengan heading "'
            .$section['heading'].'" dan target sekitar '.$words.' kata.'.PHP_EOL
            .'Ringkasan arahan: '.trim((string) $section['brief']).PHP_EOL;

        if ($region !== null) {
            $line .= 'Wilayah konteks (pakai hanya nama wilayah, tanpa klaim data faktual): '.$region.PHP_EOL;
        }

        $line .= 'Format JSON: {"heading": string, "html": string}';

        return $line;
    }
}
