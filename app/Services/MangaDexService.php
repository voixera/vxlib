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

    /** Find MangaDex manga ID by title or search query */
    public static function searchManga(string $title, int $limit = 5): ?array
    {
        $q = urlencode(trim($title));
        if ($q === '') return null;
        return Cache::remember('mangadex:search:' . md5($q), 3600, fn() => Http::getJson(self::baseUrl() . '/manga?title=' . $q . '&limit=' . $limit));
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

    public static function findChapters(array $queries): array
    {
        $chapters = [];
        foreach ($queries as $q) {
            $search = self::searchManga($q);
            $mdId = $search['data'][0]['id'] ?? null;

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
                            'title'        => "[$lang] Ch. {$chNum} - " . $chTitle,
                            'language'     => $lang,
                            'publish_date' => $pubDate,
                            'group'        => 'MangaDex Scanlation',
                            'source'       => 'mangadex',
                        ];
                    }
                    if (!empty($chapters)) break;
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
