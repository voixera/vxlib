<?php

declare(strict_types=1);

/**
 * Sanitizes external HTML (Project Gutenberg reading copies) into safe,
 * chapter-split fragments for the VoiXLib reader.
 * Allowlist-based: everything not explicitly permitted is dropped.
 */

final class ContentSanitizer
{
    private const ALLOWED_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'em', 'i', 'strong', 'b',
        'blockquote', 'ul', 'ol', 'li', 'br', 'hr', 'img', 'figure', 'figcaption', 'table',
        'thead', 'tbody', 'tr', 'th', 'td', 'sup', 'sub', 'span', 'div', 'section', 'article', 'pre'];
    private const ALLOWED_ATTRS = ['src', 'href', 'alt', 'colspan', 'rowspan'];

    /**
     * @return array{title:string, chapters: array<int, array{title:?string, html:string}>}|null
     */
    public static function splitChapters(string $rawHtml, string $baseUrl): ?array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        // Force UTF-8 interpretation regardless of the document's own declaration quirks.
        if (!$doc->loadHTML('<?xml encoding="utf-8"?><body>' . $rawHtml . '</body>', LIBXML_NOWARNING | LIBXML_NOERROR)) {
            return null;
        }
        libxml_clear_errors();

        $xp = new DOMXPath($doc);

        // Drop non-content chrome entirely.
        foreach ($xp->query('//script|//style|//link|//meta|//iframe|//svg|//form|//noscript|//head') as $junk) {
            $junk->parentNode?->removeChild($junk);
        }

        // Gutenberg boilerplate sections.
        foreach ($xp->query("//*[contains(@class,'pgheader')]|//header") as $junk) {
            $junk->parentNode?->removeChild($junk);
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) return null;

        self::sanitizeNode($body, $baseUrl);

        // Collect top-level content in document order; new chapter at each real heading.
        $chapters = [];
        $current  = ['title' => null, 'html' => ''];

        foreach ($body->childNodes as $node) {
            if ($node instanceof DOMText) {
                $text = trim($node->textContent ?? '');
                if ($text !== '') {
                    $current['html'] .= '<p>' . e(mb_substr($text, 0, 2000)) . '</p>';
                }
                continue;
            }
            if (!$node instanceof DOMElement) continue;

            $tag = strtolower($node->tagName);
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unwrap unknown containers by hoisting their children.
                foreach (iterator_to_array($node->childNodes) as $child) {
                    $body->insertBefore($child->cloneNode(true), $node);
                }
                $body->removeChild($node);
                continue;
            }

            $isHeading = in_array($tag, ['h1', 'h2', 'h3'], true)
                && !self::looksLikeFrontMatter($node->textContent ?? '');

            if ($isHeading && trim(strip_tags((string)$current['html'])) !== '') {
                $chapters[] = $current;
                $current = ['title' => null, 'html' => ''];
            }
            if ($isHeading) {
                $current['title'] = self::headingTitle($node->textContent ?? '') ?? $current['title'];
            }

            $fragment = $doc->saveHTML($node);
            if (!is_string($fragment)) continue;
            if (trim(strip_tags($fragment)) === '' && !$node->getElementsByTagName('img')->length) continue;
            $current['html'] .= $fragment;

            // Hard safety: split oversized unheaded books into ~40k-char chapters.
            if (mb_strlen(strip_tags($current['html'])) > 40000) {
                $chapters[] = $current;
                $current = ['title' => null, 'html' => ''];
            }
        }

        if (trim(strip_tags($current['html'])) !== '') $chapters[] = $current;

        $titleNode = $xp->query('//h1|//title')->item(0);
        $bookTitle = $titleNode ? self::cleanText($titleNode->textContent ?? '') : '';

        // Re-index and drop whitespace-only chapters.
        $chapters = array_values(array_filter(
            $chapters,
            fn($c) => trim(strip_tags((string)$c['html'])) !== '' || str_contains((string)$c['html'], '<img')
        ));
        if (!$chapters) return null;

        return [
            'title'    => $bookTitle,
            'chapters' => $chapters,
        ];
    }

    private static function looksLikeFrontMatter(string $text): bool
    {
        $t = mb_strtolower(self::cleanText($text));
        if ($t === '' || strlen($t) < 4) return true;
        // TOC page-number artifacts like "{ix}"
        if (preg_match('/^\{[a-z0-9]+\}$/i', trim($text))) return true;
        return str_contains($t, 'project gutenberg')
            || str_contains($t, 'produced by')
            || str_contains($t, 'transcriber')
            || str_contains($t, 'contents');
    }

    /**
     * Extract a clean chapter label. Gutenberg headings often embed
     * illustration captions before the real label ("A note for Miss Bennet.
     * CHAPTER VII.") — prefer the structured label when present.
     */
    private static function headingTitle(string $raw): ?string
    {
        $text = self::cleanText($raw);
        if ($text === '') return null;

        if (preg_match('/(chapter|part|book|stanza|canto|letter|act|scene)\s*([IVXLCDM]+|\d{1,4})\b\.?/iu', $text, $m)) {
            $num = ctype_digit($m[2]) ? $m[2] : strtoupper($m[2]);
            return ucfirst(strtolower($m[1])) . ' ' . $num;
        }

        // No structural label: keep short evocative headings only ("The Attack").
        return mb_strlen($text) <= 48 && !str_ends_with($text, '.') ? $text : null;
    }

    /** Recursively strip disallowed tags/attributes; rewrite asset URLs. */
    private static function sanitizeNode(DOMNode $node, string $baseUrl): void
    {
        if ($node->hasChildNodes()) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof DOMElement) {
                    if (!in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                        // unwrap
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        continue;
                    }
                    foreach (iterator_to_array($child->attributes ?? []) as $attr) {
                        $name = strtolower($attr->name);
                        if (!in_array($name, self::ALLOWED_ATTRS, true) || str_starts_with($name, 'on')) {
                            $child->removeAttribute($attr->name);
                            continue;
                        }
                        if ($name === 'src' || $name === 'href') {
                            $val = (string)$attr->value;
                            if (preg_match('#^\s*(javascript|data(?!:image/(png|jpeg|gif|webp)):)#i', $val)) {
                                $child->removeAttribute($name);
                                continue;
                            }
                            if ($val !== '' && !str_starts_with($val, 'http://') && !str_starts_with($val, 'https://')) {
                                $child->setAttribute($name, rtrim($baseUrl, '/') . '/' . ltrim($val, '/'));
                            }
                            if ($name === 'src') {
                                $child->setAttribute('loading', 'lazy');
                            }
                        }
                    }
                }
                self::sanitizeNode($child, $baseUrl);
            }
        }
    }

    private static function cleanText(string $text): string
    {
        $t = preg_replace('/\s+/u', ' ', trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '';
        // Strip common Gutenberg license suffixes from titles.
        $t = preg_replace('/\s*(by\s+.*|project gutenberg.*)$/iu', '', $t) ?? $t;
        return mb_substr(trim($t), 0, 160);
    }
}
