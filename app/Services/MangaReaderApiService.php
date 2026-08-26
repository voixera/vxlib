<?php

declare(strict_types=1);

/** MangaReaderApiService — Service integrasi REST API KatowProject manga-reader (KomikIndo & Mangabat). */
final class MangaReaderApiService
{
    private static function baseUrl(): string
    {
        return rtrim((string)Config::get('MANGA_READER_API_URL', 'http://komikato.bugs.today'), '/');
    }

    /** KomikIndo: Home */
    public static function getKomikIndoHome(): ?array
    {
        return Cache::remember('mr_api:komikindo:home', 3600, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/home/'));
    }

    /** KomikIndo: Daftar Komik (Pagination) */
    public static function getKomikIndoDaftar(int $page = 1): ?array
    {
        return Cache::remember('mr_api:komikindo:daftar:' . $page, 3600, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/daftar-komik/page/' . $page . '/'));
    }

    /** KomikIndo: Komik Terbaru (Pagination) */
    public static function getKomikIndoTerbaru(int $page = 1): ?array
    {
        return Cache::remember('mr_api:komikindo:terbaru:' . $page, 1800, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/komik-terbaru/page/' . $page . '/'));
    }

    /** KomikIndo: Komik Filter (manga/manhua/manhwa) */
    public static function getKomikIndoFilter(string $type, int $page = 1): ?array
    {
        $type = strtolower(trim($type));
        return Cache::remember('mr_api:komikindo:filter:' . $type . ':' . $page, 3600, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/' . urlencode($type) . '/page/' . $page . '/'));
    }

    /** KomikIndo: Search */
    public static function searchKomikIndo(string $query, int $page = 1): ?array
    {
        $q = urlencode(trim($query));
        return Cache::remember('mr_api:komikindo:search:' . md5($q) . ':' . $page, 1800, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/cari/' . $q . '/page/' . $page));
    }

    /** KomikIndo: Detail */
    public static function getKomikIndoDetail(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        return Cache::remember('mr_api:komikindo:detail:' . $ep, 3600, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/komik/' . $ep . '/'));
    }

    /** KomikIndo: Chapter */
    public static function getKomikIndoChapter(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        return Cache::remember('mr_api:komikindo:chapter:' . $ep, 7200, fn() => Http::getJson(self::baseUrl() . '/komikindo/api/chapter/' . $ep . '/'));
    }

    /** Mangabat: Search */
    public static function searchMangabat(string $query, int $page = 1): ?array
    {
        $q = urlencode(trim($query));
        return Cache::remember('mr_api:mangabat:search:' . md5($q) . ':' . $page, 1800, fn() => Http::getJson(self::baseUrl() . '/mangabat/api/search/' . $q . '/page/' . $page));
    }

    /** Mangabat: Detail */
    public static function getMangabatDetail(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        return Cache::remember('mr_api:mangabat:detail:' . $ep, 3600, fn() => Http::getJson(self::baseUrl() . '/mangabat/api/comic/' . $ep));
    }

    /** Mangabat: Chapter */
    public static function getMangabatChapter(string $endpoint): ?array
    {
        $ep = trim($endpoint, '/');
        return Cache::remember('mr_api:mangabat:chapter:' . $ep, 7200, fn() => Http::getJson(self::baseUrl() . '/mangabat/api/chapter/' . $ep));
    }
}
