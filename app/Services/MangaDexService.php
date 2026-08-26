<?php

declare(strict_types=1);

/** MangaDexService — Service integrasi MangaDex API v5 untuk Manga/Manhwa Chapters. */
final class MangaDexService implements ChapterProvider
{
    public static function getProviderName(): string
    {
        return 'MangaDex';
    }

    private static function baseUrl(): string
    {
        return rtrim((string)Config::get('MANGADEX_API_URL', 'https://api.mangadex.org'), '/');
    }

    /** Find MangaDex manga ID by title or search query with title matching score */
    public static function searchManga(string $title, int $limit = 10): ?array
    {
        $q = urlencode(trim($title));
        if ($q === '') return null;
        return Cache::remember('mangadex:search:' . md5($q), 3600, fn() => Http::getJson(self::baseUrl() . '/manga?title=' . $q . '&limit=' . $limit . '&includes[]=cover_art'));
    }

    /** Get chapter list for a MangaDex manga ID */
    public static function getChapters(string $mangaId, array $translatedLanguage = [], int $limit = 100, int $offset = 0): ?array
    {
        $mangaId = trim($mangaId);
        if ($mangaId === '') return null;

        $langQuery = !empty($translatedLanguage) ? implode('&translatedLanguage[]=', array_map('urlencode', $translatedLanguage)) : '';
        $url = self::baseUrl() . '/manga/' . $mangaId . '/feed?limit=' . $limit . '&offset=' . $offset . '&order[chapter]=asc' . ($langQuery !== '' ? '&translatedLanguage[]=' . $langQuery : '');

        return Cache::remember('mangadex:feed:' . $mangaId . ':' . md5($langQuery) . ':' . $offset, 1800, fn() => Http::getJson($url));
    }

    /** Get chapter image URLs via @Home server for a MangaDex chapter ID */
    public static function getChapterPages(string $chapterId, bool $dataSaver = false): ?array
    {
        $chapterId = trim($chapterId);
        if ($chapterId === '') return null;

        $res = Cache::remember('mangadex:at-home:' . $chapterId, 1800, fn() => Http::getJson(self::baseUrl() . '/at-home/server/' . $chapterId));
        if (!$res || ($res['result'] ?? '') !== 'ok' || empty($res['baseUrl']) || empty($res['chapter'])) {
            return null;
        }

        $baseUrl = $res['baseUrl'];
        $hash = $res['chapter']['hash'];
        $files = $dataSaver ? ($res['chapter']['dataSaver'] ?? []) : ($res['chapter']['data'] ?? []);
        $mode = $dataSaver ? 'data-saver' : 'data';

        $pages = [];
        foreach ($files as $file) {
            $pages[] = $baseUrl . '/' . $mode . '/' . $hash . '/' . $file;
        }

        return [
            'baseUrl' => $baseUrl,
            'hash'    => $hash,
            'pages'   => $pages,
        ];
    }

    public static function findMangaId(array $queries, ?int $year = null, ?string $author = null): ?string
    {
        $bestId = null;
        $bestScore = 0;
        $minThreshold = 40;

        foreach ($queries as $q) {
            $search = self::searchManga($q);
            if (empty($search['data'])) continue;

            foreach ($search['data'] as $item) {
                $attrs = $item['attributes'] ?? [];
                $titles = [];
                if (!empty($attrs['title'])) {
                    foreach ($attrs['title'] as $t) $titles[] = (string)$t;
                }
                if (!empty($attrs['altTitles'])) {
                    foreach ($attrs['altTitles'] as $alt) {
                        foreach ($alt as $t) $titles[] = (string)$t;
                    }
                }

                $score = 0;
                $normQ = strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $q)));
                if ($normQ === '') continue;

                foreach ($titles as $t) {
                    $normT = strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $t)));
                    if ($normT === '') continue;

                    if ($normQ === $normT) {
                        $score += 100;
                        break;
                    }
                    if (str_contains($normT, $normQ) || str_contains($normQ, $normT)) {
                        $score += 60;
                    }
                }

                if ($year !== null && isset($attrs['year']) && (int)$attrs['year'] === $year) {
                    $score += 20;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestId = $item['id'];
                }
            }

            if ($bestScore >= 100) break;
        }

        return $bestScore >= $minThreshold ? $bestId : null;
    }

    public static function findChapters(array $queries, ?int $year = null, ?string $author = null): array
    {
        $chapters = [];
        $mdId = self::findMangaId($queries, $year, $author);

        if ($mdId) {
            $feed = self::getChapters($mdId);
            if (!empty($feed['data'])) {
                foreach ($feed['data'] as $chItem) {
                    $attrs = $chItem['attributes'] ?? [];
                    $chNum = $attrs['chapter'] ?? '?';
                    $chTitle = $attrs['title'] ?: "Chapter {$chNum}";
                    $lang = strtoupper((string)($attrs['translatedLanguage'] ?? 'EN'));
                    $pubDate = !empty($attrs['publishAt']) ? date('d M Y', strtotime((string)$attrs['publishAt'])) : null;
                    
                    $chapters[] = [
                        'id'           => $chItem['id'],
                        'number'       => (string)$chNum,
                        'title'        => "Ch. {$chNum}" . ($attrs['title'] ? " - {$attrs['title']}" : ''),
                        'language'     => $lang,
                        'publish_date' => $pubDate,
                        'group'        => 'MangaDex',
                        'source'       => 'mangadex',
                    ];
                }
            }
        }
        return $chapters;
    }

    public static function getPages(string $chapterId): array
    {
        $res = self::getChapterPages($chapterId);
        return $res['pages'] ?? [];
    }
}
