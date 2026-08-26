<?php

declare(strict_types=1);

/** MangaReaderApiService — Service integrasi REST API KatowProject manga-reader (KomikIndo & Mangabat). */
final class MangaReaderApiService implements ChapterProvider
{
    public static function getProviderName(): string
    {
        return 'KomikIndo / Mangabat';
    }

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

    /** Helper: Ambil Rilis Komik Terbaru KomikIndo & Mangabat */
    public static function getLatestReleases(int $page = 1): array
    {
        $res = self::getKomikIndoTerbaru($page);
        if (empty($res['data'])) return [];

        $items = [];
        foreach ($res['data'] as $item) {
            $items[] = [
                'id'          => $item['endpoint'] ?? '',
                'title'       => $item['title'] ?? 'Komik Terbaru',
                'cover_url'   => $item['image'] ?? null,
                'chapter'     => $item['chapter'] ?? null,
                'media_type'  => str_contains(strtolower($item['title'] ?? ''), 'manhwa') ? 'manhwa' : 'manga',
                'type_label'  => str_contains(strtolower($item['title'] ?? ''), 'manhwa') ? 'Manhwa' : 'Manga',
                'url_detail'  => '/read/manga/1?ch=' . urlencode($item['endpoint'] ?? ''),
            ];
        }
        return $items;
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

    public static function findChapters(array $queries): array
    {
        $chapters = [];

        // 1. Try KomikIndo
        foreach ($queries as $q) {
            $kiSearch = self::searchKomikIndo($q);
            if (!empty($kiSearch['data'])) {
                $first = $kiSearch['data'][0] ?? null;
                if (!empty($first['endpoint'])) {
                    $kiDetail = self::getKomikIndoDetail($first['endpoint']);
                    if (!empty($kiDetail['chapter_list'])) {
                        foreach ($kiDetail['chapter_list'] as $chItem) {
                            $name = $chItem['name'] ?? 'Chapter';
                            $chapters[] = [
                                'id'           => $chItem['endpoint'],
                                'number'       => (string)$name,
                                'title'        => "[ID] " . $name,
                                'language'     => 'ID',
                                'publish_date' => null,
                                'group'        => 'KomikIndo',
                                'source'       => 'komikindo',
                            ];
                        }
                        if (!empty($chapters)) return $chapters;
                    }
                }
            }
        }

        // 2. Try Mangabat
        foreach ($queries as $q) {
            $mbSearch = self::searchMangabat($q);
            if (!empty($mbSearch['data'])) {
                $first = $mbSearch['data'][0] ?? null;
                if (!empty($first['endpoint'])) {
                    $mbDetail = self::getMangabatDetail($first['endpoint']);
                    if (!empty($mbDetail['chapter_list'])) {
                        foreach ($mbDetail['chapter_list'] as $chItem) {
                            $name = $chItem['name'] ?? 'Chapter';
                            $chapters[] = [
                                'id'           => $chItem['endpoint'],
                                'number'       => (string)$name,
                                'title'        => "[EN] " . $name,
                                'language'     => 'EN',
                                'publish_date' => null,
                                'group'        => 'Mangabat',
                                'source'       => 'mangabat',
                            ];
                        }
                        if (!empty($chapters)) return $chapters;
                    }
                }
            }
        }

        return $chapters;
    }

    public static function getPages(string $chapterId): array
    {
        if (str_starts_with($chapterId, 'read-')) {
            $chDetail = self::getMangabatChapter($chapterId);
            return $chDetail['image_list'] ?? [];
        }

        $chDetail = self::getKomikIndoChapter($chapterId);
        return $chDetail['image_list'] ?? [];
    }
}
