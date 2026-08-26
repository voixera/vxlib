<?php

declare(strict_types=1);

final class MediaReaderController
{
    private const TYPES = ['anime', 'manga', 'manhwa'];

    public static function show(): void
    {
        $type = strtolower((string)($_GET['type'] ?? ''));
        $id = (int)($_GET['id'] ?? 0);
        if (!in_array($type, self::TYPES, true) || $id < 1 || $id > 99999999) {
            self::notFound();
        }

        $media = AniListService::detail($id);
        if (!$media || $media['media_type'] !== $type) {
            self::notFound();
        }

        page('pages/media-reader', [
            'title' => ($type === 'anime' ? 'Menonton ' : 'Membaca ') . $media['title'] . ' — VoiXLib',
            'activeNav' => $type,
            'ogImage' => $media['cover_url'],
        ], ['m' => $media]);
    }

    private static function notFound(): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => 'Judul ini tidak ada di katalog.']);
    }
}
