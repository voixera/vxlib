<?php

declare(strict_types=1);

/**
 * ExploreController — jelajah & pencarian katalog AniList.
 * Semua angka/judul berasal dari provider; tidak ada data karangan.
 */

final class ExploreController
{
    private const TYPES = ['manga', 'manhwa'];
    private const SORTS = ['popular', 'trending', 'newest', 'oldest', 'title_asc', 'title_desc', 'score'];

    /** @return array{q:string,type:string,genre:string,status:string,sort:string,
     *                year_from:?int,year_to:?int,page:int,view:string} */
    public static function paramsFromRequest(): array
    {
        $get = $_GET;
        $yf = isset($get['year_from']) ? filter_var($get['year_from'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1910, 'max_range' => (int)date('Y')]]) : false;
        $yt = isset($get['year_to']) ? filter_var($get['year_to'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1910, 'max_range' => (int)date('Y') + 1]]) : false;

        return [
            'q'         => isset($get['q']) ? mb_substr(trim((string)$get['q']), 0, 100) : '',
            'type'      => in_array($get['type'] ?? '', self::TYPES, true) ? $get['type'] : '',
            'genre'     => preg_match('/^[A-Za-z][A-Za-z \-]{1,20}$/', $get['genre'] ?? '') ? $get['genre'] : '',
            'status'    => in_array($get['status'] ?? '', ['releasing', 'finished', 'upcoming', 'hiatus'], true) ? $get['status'] : '',
            'sort'      => in_array($get['sort'] ?? '', self::SORTS, true) ? $get['sort'] : 'popular',
            'year_from' => $yf === false ? null : $yf,
            'year_to'   => $yt === false ? null : $yt,
            'page'      => max(1, (int)($get['page'] ?? 1)),
            'view'      => ($get['view'] ?? '') === 'list' ? 'list' : 'grid',
        ];
    }

    public static function browse(array $params): array
    {
        $res = AniListService::browse($params);
        if ($res === null) return ['items' => [], 'total' => null, 'has_next' => false, 'error' => 'provider_unavailable', 'page' => $params['page']];
        return $res + ['error' => null];
    }

    /** Rak buku domain publik — teks nyata dari Project Gutenberg, legal dibaca penuh. */
    public static function klasik(): void
    {
        $page = max(1, min(50, (int)($_GET['page'] ?? 1)));
        $result = GutenbergService::popular($page);

        page('pages/klasik', [
            'title'       => 'Klasik Domain Publik — VoiXLib',
            'description' => 'Karya klasik domain publik yang bisa dibaca langsung di VoiXLib dengan pengalaman membalik halaman.',
            'activeNav'   => 'klasik',
        ], [
            'books'     => $result['books'] ?? [],
            'nextPage'  => $result['next_page'] ?? null,
            'page'      => $page,
        ]);
    }

    public static function explore(): void
    {
        $params = self::paramsFromRequest();
        page('pages/explore', [
            'title'       => 'Jelajahi Katalog — VoiXLib',
            'description' => 'Temukan manga dan manhwa dari katalog nyata. Cari berdasarkan genre, status, tahun, dan popularitas.',
            'activeNav'   => $params['type'] !== '' ? $params['type'] : 'explore',
            'scripts'     => ['catalog.js'],
        ], [
            'params'     => $params,
            'result'     => self::browse($params),
            'isSearch'   => false,
        ]);
    }

    public static function searchPage(): void
    {
        $params = self::paramsFromRequest();
        if ($params['sort'] === 'score') $params['sort'] = 'popular';
        if (empty($params['q'])) $params['sort'] = 'trending';
        page('pages/explore', [
            'title'       => ($params['q'] !== '' ? '“' . $params['q'] . '” — Pencarian' : 'Pencarian') . ' — VoiXLib',
            'description' => 'Cari manga dan manhwa di seluruh katalog VoiXLib.',
            'activeNav'   => 'explore',
            'scripts'     => ['catalog.js'],
        ], [
            'params'     => $params,
            'result'     => self::browse($params),
            'isSearch'   => true,
        ]);
    }
}
