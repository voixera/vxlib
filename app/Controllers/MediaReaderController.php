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

        // Collect search query candidates
        $queries = array_filter(array_unique([
            $media['title'] ?? '',
            $media['title_romaji'] ?? '',
            $media['alt_title'] ?? '',
        ]));

        // 1. Try Febry Manga API (MangaMint ID)
        foreach ($queries as $q) {
            $fSearch = FebryMangaApiService::search($q);
            $mangaList = $fSearch['manga_list'] ?? ($fSearch['data'] ?? []);
            if (!empty($mangaList)) {
                $first = $mangaList[0] ?? null;
                $ep = $first['endpoint'] ?? null;
                if ($ep) {
                    $fDetail = FebryMangaApiService::getDetail($ep);
                    $chList = $fDetail['chapter'] ?? ($fDetail['chapter_list'] ?? []);
                    if (!empty($chList)) {
                        foreach ($chList as $chItem) {
                            $chapters[] = [
                                'id' => $chItem['chapter_endpoint'] ?? ($chItem['endpoint'] ?? ''),
                                'number' => $chItem['chapter_title'] ?? ($chItem['name'] ?? 'Chapter'),
                                'title' => $chItem['chapter_title'] ?? ($chItem['name'] ?? 'Chapter'),
                                'source' => 'febry_manga',
                            ];
                        }
                        if (!empty($chapters)) break;
                    }
                }
            }
        }

        // 2. Try MangaDex with all query candidates
        if (empty($chapters)) {
            foreach ($queries as $q) {
                $search = MangaDexService::searchManga($q);
                $mdId = $search['data'][0]['id'] ?? null;

                if ($mdId) {
                    $feed = MangaDexService::getChapters($mdId);
                    if (!empty($feed['data'])) {
                        foreach ($feed['data'] as $chItem) {
                            $attrs = $chItem['attributes'] ?? [];
                            $chNum = $attrs['chapter'] ?? '?';
                            $chTitle = $attrs['title'] ?: "Chapter {$chNum}";
                            $lang = strtoupper((string)($attrs['translatedLanguage'] ?? 'EN'));
                            $chapters[] = [
                                'id' => $chItem['id'],
                                'number' => $chNum,
                                'title' => "[$lang] Ch. {$chNum} - " . $chTitle,
                                'source' => 'mangadex',
                            ];
                        }
                        if (!empty($chapters)) break;
                    }
                }
            }
        }

        // 3. Fallback to KomikIndo
        if (empty($chapters)) {
            foreach ($queries as $q) {
                $kiSearch = MangaReaderApiService::searchKomikIndo($q);
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
                            if (!empty($chapters)) break;
                        }
                    }
                }
            }
        }

        // 4. Fallback to Mangabat
        if (empty($chapters)) {
            foreach ($queries as $q) {
                $mbSearch = MangaReaderApiService::searchMangabat($q);
                if (!empty($mbSearch['data'])) {
                    $first = $mbSearch['data'][0] ?? null;
                    if (!empty($first['endpoint'])) {
                        $mbDetail = MangaReaderApiService::getMangabatDetail($first['endpoint']);
                        if (!empty($mbDetail['chapter_list'])) {
                            foreach ($mbDetail['chapter_list'] as $chItem) {
                                $chapters[] = [
                                    'id' => $chItem['endpoint'],
                                    'number' => $chItem['name'] ?? 'Chapter',
                                    'title' => $chItem['name'] ?? 'Chapter',
                                    'source' => 'mangabat',
                                ];
                            }
                            if (!empty($chapters)) break;
                        }
                    }
                }
            }
        }

        // Select active chapter & fetch pages
        if (!empty($chapters)) {
            if (!$selectedChapterId || !array_filter($chapters, fn($c) => $c['id'] === $selectedChapterId)) {
                $selectedChapterId = $chapters[0]['id'];
            }

            $activeCh = array_values(array_filter($chapters, fn($c) => $c['id'] === $selectedChapterId))[0] ?? $chapters[0];

            if (($activeCh['source'] ?? '') === 'febry_manga') {
                $fChDetail = FebryMangaApiService::getChapter($activeCh['id']);
                $selectedChapterPages = $fChDetail['chapter_image'] ?? ($fChDetail['image_list'] ?? []);
                // If nested object format (array of objects with image_url)
                if (!empty($selectedChapterPages) && is_array($selectedChapterPages[0] ?? null)) {
                    $selectedChapterPages = array_map(fn($img) => $img['chapter_image_link'] ?? ($img['image_url'] ?? ''), $selectedChapterPages);
                }
            } elseif (($activeCh['source'] ?? '') === 'mangadex') {
                $pagesRes = MangaDexService::getChapterPages($activeCh['id']);
                $selectedChapterPages = $pagesRes['pages'] ?? [];
            } elseif (($activeCh['source'] ?? '') === 'komikindo') {
                $chDetail = MangaReaderApiService::getKomikIndoChapter($activeCh['id']);
                $selectedChapterPages = $chDetail['image_list'] ?? [];
            } elseif (($activeCh['source'] ?? '') === 'mangabat') {
                $chDetail = MangaReaderApiService::getMangabatChapter($activeCh['id']);
                $selectedChapterPages = $chDetail['image_list'] ?? [];
            }
        }

        $art = empty($chapters) ? NekosService::safeImage() : null;

        page('pages/media-reader', [
            'title' => 'Membaca ' . $media['title'] . ' — VoiXLib',
            'activeNav' => $type,
            'ogImage' => $media['cover_url'],
        ], [
            'm' => $media,
            'art' => $art,
            'chapters' => $chapters,
            'selectedChapterId' => $selectedChapterId,
            'selectedChapterPages' => $selectedChapterPages,
        ]);
    }

    private static function notFound(): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => 'Judul ini tidak ada di katalog.']);
    }
}
