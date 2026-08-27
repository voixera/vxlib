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

        // Coba resolusi seri di provider pembaca (WeebCentral) agar judul
        // yang tampil di VoiXLib benar-benar bisa dibaca langsung di situs.
        $wb = self::resolveReader($media);

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
            'chapters'    => $wb['chapters'],
            'wb'          => $wb,
        ]);
    }

    /**
     * Cari seri di provider pembaca lewat pencocokan judul, lalu kembalikan
     * daftar chapter nyata (dengan URL reader VoiXLib).
     * @return array{ok:bool, seriesId:?string, chapters:array, first:?string, count:int}
     */
    private static function resolveReader(array $media): array
    {
        $empty = ['ok' => false, 'seriesId' => null, 'chapters' => [], 'first' => null, 'count' => 0];

        $candidates = array_values(array_filter(array_unique(array_merge(
            [(string)($media['title'] ?? ''), (string)($media['title_romaji'] ?? ''), (string)($media['alt_title'] ?? '')],
            is_array($media['synonyms'] ?? null) ? $media['synonyms'] : []
        ))));

        $best = null;
        $bestScore = 0;
        $bestQuery = '';
        foreach ($candidates as $q) {
            if ($q === '') continue;
            try {
                $res = WeebCentralProvider::search($q);
            } catch (Throwable $e) {
                continue;
            }
            if (empty($res['items'])) continue;

            $qn = self::norm($q);
            foreach ($res['items'] as $it) {
                $tn = self::norm($it['title'] ?? '');
                if ($tn === '') continue;
                if ($tn === $qn) {
                    $score = 1.0;
                } elseif (str_contains($tn, $qn) || str_contains($qn, $tn)) {
                    $score = 0.85;
                } else {
                    similar_text($tn, $qn, $pct);
                    $score = $pct / 100;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $it;
                    $bestQuery = $q;
                }
            }
            // Kalau sudah cocok kuat, stop lebih awal
            if ($bestScore >= 0.85) break;
        }

        if (!$best || $bestScore < 0.6) {
            return $empty;
        }

        try {
            $seriesId = $best['id'];
            $raw = WeebCentralProvider::chapters($seriesId);
        } catch (Throwable $e) {
            return $empty;
        }

        if (empty($raw)) {
            return $empty;
        }

        $chapters = array_map(fn($c) => [
            'id'           => $c['id'],
            'title'        => $c['title'],
            'language'     => null,
            'publish_date' => $c['date'] ? substr((string)$c['date'], 0, 10) : null,
            'group'        => 'WeebCentral',
            'source'       => 'weebcentral',
            'url'          => '/manga/read/' . $seriesId . '/' . $c['id'],
        ], $raw);

        return [
            'ok'       => true,
            'seriesId' => $seriesId,
            'chapters' => $chapters,
            'first'    => $raw[0]['id'],
            'count'    => count($raw),
            'query'    => $bestQuery,
        ];
    }

    private static function norm(string $t): string
    {
        return (string)preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($t)));
    }

    private static function notFound(string $message): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => $message]);
    }
}
