<?php

declare(strict_types=1);

/**
 * MangaController — alur baca manga/manhwa/manhua di dalam VoiXLib.
 *
 * Flow: cari → detail → daftar chapter → pilih chapter → halaman → baca.
 * Semua data dari provider (default WeebCentral) lewat MangaProviderFactory,
 * sehingga provider bisa diganti tanpa mengubah UI.
 */

final class MangaController
{
    private static function provider(): string
    {
        return MangaProviderFactory::className();
    }

    public static function search(): void
    {
        $q = isset($_GET['q']) ? mb_substr(trim((string)$_GET['q']), 0, 100) : '';
        $items = [];
        if ($q !== '') {
            $p = self::provider();
            $res = $p::search($q);
            $items = $res['items'] ?? [];
        }

        page('pages/manga-search', [
            'title'       => ($q !== '' ? '“' . $q . '” — ' : '') . 'Cari Manga & Manhwa — VoiXLib',
            'description' => 'Cari dan baca manga, manhwa, dan manhua langsung di VoiXLib.',
            'activeNav'   => 'baca',
        ], [
            'query'  => $q,
            'items'  => $items,
        ]);
    }

    public static function detail(): void
    {
        $id = isset($_GET['series']) ? trim((string)$_GET['series']) : '';
        if ($id === '') {
            self::notFound('Judul tidak valid.');
        }

        $p = self::provider();
        $series = $p::detail($id);
        if ($series === null) {
            self::notFound('Judul ini tidak ditemukan di penyedia baca.');
        }

        $chapters = $p::chapters($id);

        page('pages/manga-detail', [
            'title'     => ($series['title'] ?? 'Manga') . ' — VoiXLib',
            'activeNav' => 'baca',
            'ogImage'   => $series['cover'] ?? null,
        ], [
            'series'   => $series,
            'chapters' => $chapters,
        ]);
    }

    public static function read(): void
    {
        $seriesId = isset($_GET['series']) ? trim((string)$_GET['series']) : '';
        $chapterId = isset($_GET['chapter']) ? trim((string)$_GET['chapter']) : '';
        if ($seriesId === '') {
            self::notFound('Chapter tidak valid.');
        }

        $p = self::provider();
        $data = $p::read($seriesId, $chapterId);
        if ($data === null) {
            self::notFound('Chapter tidak ditemukan di penyedia baca.');
        }

        page('pages/manga-reader', [
            'title'     => 'Membaca ' . ($data['series']['title'] ?? '') . ' — VoiXLib',
            'activeNav' => 'baca',
            'chromeless' => true,
            'scripts'   => ['reader-ui.js'],
        ], [
            'data' => $data,
        ]);
    }

    private static function notFound(string $message): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => 'baca'], ['message' => $message]);
    }
}
