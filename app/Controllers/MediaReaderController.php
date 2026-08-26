<?php

declare(strict_types=1);

final class MediaReaderController
{
    private const TYPES = ['manga', 'manhwa'];

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

        $chapters = [];
        $selectedChapterPages = [];
        $selectedChapterId = $_GET['ch'] ?? null;
        $animeInfo = null;

        if ($type === 'anime') {
            // Fetch AniPub info for anime stream/episodes
            $animeInfo = AniPubService::info($id);
        } else {
            // 1. Try MangaDex
            $search = MangaDexService::searchManga($media['title']);
            $mdId = $search['data'][0]['id'] ?? null;

            if ($mdId) {
                $feed = MangaDexService::getChapters($mdId, ['id', 'en']);
                if (!empty($feed['data'])) {
                    foreach ($feed['data'] as $chItem) {
                        $attrs = $chItem['attributes'] ?? [];
                        $chNum = $attrs['chapter'] ?? '?';
                        $chTitle = $attrs['title'] ?: "Chapter {$chNum}";
                        $chapters[] = [
                            'id' => $chItem['id'],
                            'number' => $chNum,
                            'title' => "Cap. {$chNum} - " . $chTitle,
                            'source' => 'mangadex',
                        ];
                    }
                }
            }

            // 2. Fallback to KomikIndo / Mangabat if MangaDex empty
            if (empty($chapters)) {
                $kiSearch = MangaReaderApiService::searchKomikIndo($media['title']);
                if (!empty($kiSearch['data'])) {
                    $first = $kiSearch['data'][0] ?? null;
                    if (!empty($first['endpoint'])) {
                        $kiDetail = MangaReaderApiService::getKomikIndoDetail($first['endpoint']);
                        if (!empty($kiDetail['chapter_list'])) {
                            foreach ($kiDetail['chapter_list'] as $chItem) {
                                $chapters[] = [
                                    'id' => $chItem['endpoint'],
                                    'number' => $chItem['name'] ?? 'Chapter',
                                    'title' => $chItem['name'] ?? 'Chapter',
                                    'source' => 'komikindo',
                                ];
                            }
                        }
                    }
                }
            }

            // Select active chapter and fetch pages
            if (!empty($chapters)) {
                if (!$selectedChapterId || !array_filter($chapters, fn($c) => $c['id'] === $selectedChapterId)) {
                    $selectedChapterId = $chapters[0]['id'];
                }

                $activeCh = array_values(array_filter($chapters, fn($c) => $c['id'] === $selectedChapterId))[0] ?? $chapters[0];

                if (($activeCh['source'] ?? '') === 'mangadex') {
                    $pagesRes = MangaDexService::getChapterPages($activeCh['id']);
                    $selectedChapterPages = $pagesRes['pages'] ?? [];
                } elseif (($activeCh['source'] ?? '') === 'komikindo') {
                    $chDetail = MangaReaderApiService::getKomikIndoChapter($activeCh['id']);
                    $selectedChapterPages = $chDetail['image_list'] ?? [];
                }
            }
        }

        $art = empty($chapters) && empty($animeInfo) ? NekosService::safeImage() : null;

        page('pages/media-reader', [
            'title' => ($type === 'anime' ? 'Menonton ' : 'Membaca ') . $media['title'] . ' — VoiXLib',
            'activeNav' => $type,
            'ogImage' => $media['cover_url'],
        ], [
            'm' => $media,
            'art' => $art,
            'chapters' => $chapters,
            'selectedChapterId' => $selectedChapterId,
            'selectedChapterPages' => $selectedChapterPages,
            'animeInfo' => $animeInfo,
        ]);
    }

    private static function notFound(): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => 'Judul ini tidak ada di katalog.']);
    }
}
