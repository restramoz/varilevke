<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizerService;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerServiceTest extends TestCase
{
    private HtmlSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizerService;
    }

    public function test_strips_script_tags_with_content(): void
    {
        $out = $this->sanitizer->sanitize('<p>aman</p><script>alert("xss")</script>');

        self::assertStringNotContainsString('script', $out);
        self::assertStringNotContainsString('alert', $out);
        self::assertStringContainsString('<p>aman</p>', $out);
    }

    public function test_strips_event_handler_attributes(): void
    {
        $out = $this->sanitizer->sanitize('<p onclick="evil()" onmouseover="evil()">text</p>');

        self::assertSame('<p>text</p>', $out);
    }

    public function test_strips_unsafe_url_protocols(): void
    {
        $out = $this->sanitizer->sanitize(
            '<a href="javascript:alert(1)">x</a><a href="data:text/html,evil">y</a><a href="https://example.com">z</a>'
        );

        self::assertStringNotContainsString('javascript:', $out);
        self::assertStringNotContainsString('data:', $out);
        self::assertStringContainsString('href="https://example.com"', $out);
    }

    public function test_keeps_valid_tables_and_wraps_them_for_mobile(): void
    {
        $out = $this->sanitizer->sanitize(
            '<table><thead><tr><th>Hari</th></tr></thead><tbody><tr><td>Senin</td></tr></tbody></table>'
        );

        self::assertStringContainsString('<div class="article-table-scroll">', $out);
        self::assertStringContainsString('<thead>', $out);
        self::assertStringContainsString('Senin', $out);
    }

    public function test_headings_lists_and_links_survive(): void
    {
        $out = $this->sanitizer->sanitize(
            '<h2>Judul</h2><ul><li><a href="/aturan" title="Aturan">Aturan K3</a></li></ul><em>miring</em>'
        );

        self::assertStringContainsString('<h2>Judul</h2>', $out);
        self::assertStringContainsString('<li>', $out);
        self::assertStringContainsString('href="/aturan"', $out);
        self::assertStringContainsString('<em>miring</em>', $out);
    }

    public function test_unwrapped_tags_like_div_and_h1_are_flattened(): void
    {
        $out = $this->sanitizer->sanitize('<div><h1>Besar</h1><p>Isi</p></div>');

        self::assertStringNotContainsString('<div', $out);
        self::assertStringContainsString('Besar', $out);
        self::assertStringContainsString('<p>Isi</p>', $out);
    }

    public function test_iframe_and_object_are_removed_entirely(): void
    {
        $out = $this->sanitizer->sanitize('<p>x</p><iframe src="https://evil.example"></iframe><object data="evil"></object>');

        self::assertStringNotContainsString('evil.example', $out);
        self::assertStringContainsString('<p>x</p>', $out);
    }

    public function test_empty_input_returns_empty_string(): void
    {
        self::assertSame('', $this->sanitizer->sanitize('   '));
    }
}
