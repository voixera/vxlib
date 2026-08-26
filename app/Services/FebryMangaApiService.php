<?php

declare(strict_types=1);

/** FebryMangaApiService — Service integrasi febryardiansyah/manga-api (MangaMint API Bahasa Indonesia). */
final class FebryMangaApiService
{
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
}
