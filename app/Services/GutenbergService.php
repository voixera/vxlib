<?php

declare(strict_types=1);

/**
 * Project Gutenberg service (via Gutendex metadata API + gutenberg.org content).
 * Timeouts, caching and validation handled here — never in page controllers.
 */

final class GutenbergService
{
    private const API = 'https://gutendex.com/books/';
    private const META_TTL   = 86400;     // 1 day
    private const HTML_TTL   = 604800;    // 7 days

    /** Metadata for a single Gutenberg id. */
    public static function meta(int $gutenbergId): ?array
    {
        $id = self::sanitizeId($gutenbergId);
        if ($id === null) return null;

        return Cache::remember('guten:meta:' . $id, self::META_TTL, function () use ($id) {
            $res = Http::getJson(self::API . '?ids=' . $id);
            $row = $res['results'][0] ?? null;
            if (!$row || (int)($row['id'] ?? 0) !== $id) return null;
            return $row;
        });
    }

    /**
     * Fetch a Gutenberg reading copy, sanitize it and split into chapters.
     * Returns ['title' => string, 'chapters' => [['title' => ?string, 'html' => string], …]]
     * or null on failure.
     */
    public static function readableContent(string $readUrl, int $gutenbergId): ?array
    {
        $id = self::sanitizeId($gutenbergId);
        if ($id === null || !str_contains($readUrl, 'gutenberg.org')) return null;

        return Cache::remember('guten:content:' . $id, self::HTML_TTL, function () use ($readUrl, $id) {
            $html = Http::getText($readUrl, 30);
            if ($html === null || strlen($html) < 500) return null;
            return ContentSanitizer::splitChapters($html, 'https://www.gutenberg.org');
        });
    }

    public static function sanitizeId(int|string $raw): ?int
    {
        $n = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99999]]);
        return $n === false ? null : $n;
    }
}
