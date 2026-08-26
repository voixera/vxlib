<?php

declare(strict_types=1);

/**
 * MediaDetailController — halaman detail /detail/{type}/{id}.
 * Metadata penuh dari AniList; baris lokal dibuat agar perpustakaan/bookmark punya FK.
 */

final class MediaDetailController
{
    private const TYPES = ['manga', 'manhwa'];

    public static function show(): void
    {
        $type = strtolower((string)($_GET['type'] ?? ''));
        $id = (int)($_GET['id'] ?? 0);
        if (!in_array($type, self::TYPES, true) || $id < 1 || $id > 99999999) {
            self::notFound('Tautan judul itu tidak valid.');
        }

        $media = AniListService::detail($id);
        if (!$media || $media['media_type'] !== $type) {
            self::notFound('Judul ini tidak ada di katalog.');
        }

        // Baris lokal untuk FK perpustakaan / bookmark / riwayat (best-effort).
        $bookId = SupabaseClient::configured() ? (new CatalogRepository())->ensureLocal($media) : null;

        $user = Auth::user();
        $shelfStatus = null;
        $progress = null;
        $bookmarked = false;
        if ($user && $bookId !== null) {
            $library = new LibraryRepository();
            $shelfStatus = $library->getStatus((int)$user['id'], $bookId);
            $progress = $library->progress((int)$user['id'], $bookId);
            $bookmarked = (bool)array_filter(
                $library->bookmarks((int)$user['id'], $bookId),
                fn($b) => ($b['location'] ?? '') !== ''
            );
            if (Auth::check()) $library->touchHistory((int)$user['id'], $bookId);
        }

        // Search Query Candidates
        $queries = array_filter(array_unique([
            $media['title'] ?? '',
            $media['title_romaji'] ?? '',
            $media['alt_title'] ?? '',
        ]));

        $chapters = [];
        try {
            $year = isset($media['year']) ? (int)$media['year'] : null;
            $author = $media['author'] ?? null;
            $chapters = MangaDexService::findChapters($queries, $year, $author);
        } catch (Throwable $e) {
            $chapters = [];
        }

        page('pages/detail', [
            'title'       => $media['title'] . ' — VoiXLib',
            'description' => mb_substr((string)($media['description'] ?? ''), 0, 160) ?: ('Detail ' . $media['type_label'] . ' ' . $media['title'] . ' di VoiXLib.'),
            'activeNav'   => $type,
            'ogType'      => 'video.tv_show',
            'ogImage'     => $media['cover_url'],
            'scripts'     => ['book.js'],
        ], [
            'm'           => $media,
            'bookId'      => $bookId,
            'shelfStatus' => $shelfStatus,
            'hasBookmark' => $bookmarked,
            'progress'    => $progress,
            'migrationOk' => $bookId !== null,
            'chapters'    => $chapters,
        ]);
    }

    private static function notFound(string $message): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => $message]);
    }
}
