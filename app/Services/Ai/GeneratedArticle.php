<?php

namespace App\Services\Ai;

/**
 * Validated draft output. Values here have been measured server-side; the
 * article form receives them for the admin to review and submit explicitly.
 */
final class GeneratedArticle
{
    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     */
    public function __construct(
        public readonly string $title,
        public readonly string $excerpt,
        public readonly string $html,
        public readonly array $faqs,
        public readonly int $wordCount,
    ) {}

    /**
     * Flat payload for the admin form JS.
     *
     * @return array{title: string, excerpt: string, content: string, faqs: list<array{question: string, answer: string}>, word_count: int, reading_time: int}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->html,
            'faqs' => $this->faqs,
            'word_count' => $this->wordCount,
            'reading_time' => (int) max(1, ceil($this->wordCount / 200)),
        ];
    }
}
