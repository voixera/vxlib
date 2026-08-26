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

    /** Popular public-domain books (real Gutendex data), paginated. */
    public static function popular(int $page = 1): ?array
    {
        $page = max(1, min(50, $page));
        return Cache::remember('guten:popular:' . $page, self::META_TTL, function () use ($page) {
            $res = Http::getJson(self::API . '?sort=popular&page=' . $page);
            if (!$res || !isset($res['results'])) return null;
            $books = [];
            foreach ($res['results'] as $row) {
                $authors = (array)($row['authors'] ?? []);
                $firstAuthor = $authors[0] ?? null;
                $books[] = [
                    'id'         => (int)$row['id'],
                    'title'      => (string)($row['title'] ?? 'Tanpa judul'),
                    'author'     => $firstAuthor ? (string)($firstAuthor['name'] ?? '') : 'Anonim',
                    'year'       => $firstAuthor ? ($firstAuthor['birth_year'] ?? null) : null,
                    'cover_url'  => $row['formats']['image/jpeg'] ?? null,
                    'languages'  => implode(', ', (array)($row['languages'] ?? [])),
                    'downloads'  => (int)($row['download_count'] ?? 0),
                    'url_detail' => '/baca/gutenberg:' . (int)$row['id'],
                    'media_type' => 'text',
                    'type_label' => 'Klasik',
                    'readable'   => true,
                ];
            }
            return ['books' => $books, 'next_page' => $res['next'] !== null ? $page + 1 : null];
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
