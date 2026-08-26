<?php

declare(strict_types=1);

/** FebryMangaApiService — Service integrasi febryardiansyah/manga-api (MangaMint API Bahasa Indonesia). */
final class FebryMangaApiService implements ChapterProvider
{
    public static function getProviderName(): string
    {
        return 'MangaMint ID';
    }

    private static function baseUrl(): string
    {
        return rtrim((string)Config::get('FEBRY_MANGA_API_URL', 'https://mangamint.kaedenoki.net/api'), '/');
    }

    /** Search manga by query */
    public static function search(string $query): ?array
    {
        $q = urlencode(trim($query));
        if ($q === '') return null;
        return Cache::remember('febry_manga:search:' . md5($q), 1800, fn() => Http::getJson(self::baseUrl() . '/search/' . $q));
    }

    /** Get manga detail & chapter list */
    public static function getDetail(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        if ($ep === '') return null;
        return Cache::remember('febry_manga:detail:' . $ep, 3600, fn() => Http::getJson(self::baseUrl() . '/manga/detail/' . $ep));
    }

    /** Get chapter pages */
    public static function getChapter(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        if ($ep === '') return null;
        return Cache::remember('febry_manga:chapter:' . $ep, 7200, fn() => Http::getJson(self::baseUrl() . '/chapter/' . $ep));
    }

    /** Get popular manga */
    public static function getPopular(int $page = 1): ?array
    {
        return Cache::remember('febry_manga:popular:' . $page, 3600, fn() => Http::getJson(self::baseUrl() . '/manga/popular/' . $page));
    }

    public static function findChapters(array $queries): array
    {
        $chapters = [];
        foreach ($queries as $q) {
            $fSearch = self::search($q);
            $mangaList = $fSearch['manga_list'] ?? ($fSearch['data'] ?? []);
            if (!empty($mangaList)) {
                $first = $mangaList[0] ?? null;
                $ep = $first['endpoint'] ?? null;
                if ($ep) {
                    $fDetail = self::getDetail($ep);
                    $chList = $fDetail['chapter'] ?? ($fDetail['chapter_list'] ?? []);
                    if (!empty($chList)) {
                        foreach ($chList as $chItem) {
                            $name = $chItem['chapter_title'] ?? ($chItem['name'] ?? 'Chapter');
                            $chapters[] = [
                                'id'           => $chItem['chapter_endpoint'] ?? ($chItem['endpoint'] ?? ''),
                                'number'       => (string)$name,
                                'title'        => "[ID] " . $name,
                                'language'     => 'ID',
                                'publish_date' => null,
                                'group'        => 'MangaMint Bahasa Indonesia',
                                'source'       => 'febry_manga',
                            ];
                        }
                        if (!empty($chapters)) break;
                    }
                }
            }
        }
        return $chapters;
    }

    public static function getPages(string $chapterId): array
    {
        $fChDetail = self::getChapter($chapterId);
        $pages = $fChDetail['chapter_image'] ?? ($fChDetail['image_list'] ?? []);
        if (!empty($pages) && is_array($pages[0] ?? null)) {
            $pages = array_map(fn($img) => $img['chapter_image_link'] ?? ($img['image_url'] ?? ''), $pages);
        }
        return array_values(array_filter($pages));
    }
}
