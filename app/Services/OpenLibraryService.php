<?php

declare(strict_types=1);

/**
 * Open Library service — used for metadata enrichment and cover lookup.
 */

final class OpenLibraryService
{
    private const SEARCH = 'https://openlibrary.org/search.json';
    private const COVERS = 'https://covers.openlibrary.org';
    private const TTL    = 604800; // 7 days

    /**
     * Best-effort match by title+author.
     * Returns ['isbn' => ?string, 'cover_url' => ?string, 'page_count' => ?int,
     *          'first_publish_year' => ?int, 'description' => ?string] or null.
     */
    public static function enrich(string $title, string $author): ?array
    {
        $key = 'ol:enrich:' . md5(strtolower($title) . '|' . strtolower($author));
        return Cache::remember($key, self::TTL, function () use ($title, $author) {
            $query = http_build_query([
                'q'      => $title . ' ' . $author,
                'fields' => 'key,title,author_name,first_publish_year,number_of_pages_median,isbn,cover_i,description,first_sentence',
                'limit'  => 3,
            ]);
            $res = Http::getJson(self::SEARCH . '?' . $query);
            if (empty($res['docs'])) return null;

            // Prefer a doc whose author surname matches.
            $surname = mb_strtolower(mb_substr($author, 0, 8));
            $doc = null;
            foreach ($res['docs'] as $candidate) {
                foreach (($candidate['author_name'] ?? []) as $an) {
                    if (str_starts_with(mb_strtolower((string)$an), $surname)) { $doc = $candidate; break 2; }
                }
            }
            $doc ??= $res['docs'][0];

            $isbn = null;
            foreach ((array)($doc['isbn'] ?? []) as $cand) {
                if (strlen((string)$cand) === 13 && str_starts_with((string)$cand, '978')) { $isbn = (string)$cand; break; }
            }

            $desc = $doc['first_sentence'][0] ?? null;
            if (!$desc && isset($doc['description'])) {
                $desc = is_array($doc['description']) ? ($doc['description']['value'] ?? null) : $doc['description'];
            }

            return [
                'isbn'               => $isbn,
                'cover_url'          => isset($doc['cover_i']) ? self::COVERS . '/b/id/' . (int)$doc['cover_i'] . '-L.jpg' : null,
                'page_count'         => isset($doc['number_of_pages_median']) ? (int)$doc['number_of_pages_median'] : null,
                'first_publish_year' => isset($doc['first_publish_year']) ? (int)$doc['first_publish_year'] : null,
                'description'        => (is_string($desc) && strlen($desc) >= 60) ? trim($desc) : null,
            ];
        });
    }

    /** Verify an image URL actually resolves (HEAD). Cached negative results too. */
    public static function coverExists(string $url): bool
    {
        $key = 'img:head:' . md5($url);
        $cached = Cache::get($key);
        if ($cached !== null) return (bool)$cached;
        $ok = Http::headOk($url);
        Cache::set($key, $ok, $ok ? self::TTL : 3600);
        return $ok;
    }
}
