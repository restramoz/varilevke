<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Input-boundary HTML sanitizer with an explicit allowlist. Everything not on
 * the list is stripped (or, for dangerous tags, removed with contents).
 * Applied by the article controller before persistence; never depended on at
 * render time.
 */
final class HtmlSanitizerService
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
        'strong', 'b', 'em', 'i', 'u', 'blockquote', 'code', 'pre',
        'a', 'img', 'figure', 'figcaption', 'span',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Strip these with their contents — they are pure attack surface. */
    private const DANGEROUS_TAGS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'form', 'input', 'button', 'select', 'textarea', 'link', 'meta', 'base',
        'svg', 'math', 'applet', 'noscript', 'template',
    ];

    /** Attributes allowed per tag; global attrs are merged into every entry. */
    private const ATTRIBUTES = [
        'global' => ['class'],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'],
        'ol' => ['start'],
    ];

    private const SAFE_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        // Wrapping in a synthetic div lets us recover a clean fragment.
        $ok = $dom->loadHTML(
            '<!DOCTYPE html><html><body><div id="sanitizer-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );
        libxml_clear_errors();

        if (! $ok) {
            return (string) htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        $root = $dom->getElementById('sanitizer-root');

        if ($root === null) {
            return '';
        }

        $this->sanitizeNode($root);

        $fragment = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $fragment .= $dom->saveHTML($child);
        }

        return $this->wrapTables(trim($fragment));
    }

    private function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DANGEROUS_TAGS, true)) {
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap: keep children, drop the tag itself.
                    $this->replaceNodeWithChildren($child);

                    continue;
                }

                $this->filterAttributes($child, $tag);
            } elseif ($child->nodeType === \XML_COMMENT_NODE) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            $this->sanitizeNode($child);
        }
    }

    private function filterAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::ATTRIBUTES['global'], self::ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if ($name === 'id') {
                continue; // strip ids so pasted document anchors can't hijack in-page state
            }

            $isHandler = str_starts_with($name, 'on');
            $notAllowed = ! in_array($name, $allowed, true);

            if ($isHandler || $notAllowed) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! $this->isSafeUrl((string) $attribute->value)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    private function isSafeUrl(string $url): bool
    {
        if ($url === '' || $url === '#' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $clean = trim(str_replace(["\t", "\n", "\r", "\0", ' '], '', $url));
        $scheme = strtolower((string) (parse_url($clean, PHP_URL_SCHEME) ?: ''));

        return $scheme !== '' && in_array($scheme, self::SAFE_URL_SCHEMES, true);
    }

    private function replaceNodeWithChildren(DOMElement $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $parent->insertBefore($child->cloneNode(true), $node);
        }

        $parent->removeChild($node);
    }

    /**
     * Every top-level <table> gets a horizontally scrollable wrapper so wide
     * tables never break the page on mobile.
     */
    private function wrapTables(string $html): string
    {
        return (string) preg_replace(
            '/(<table\b[^>]*>.*?<\/table>)/is',
            '<div class="article-table-scroll">$1</div>',
            $html
        );
    }
}
