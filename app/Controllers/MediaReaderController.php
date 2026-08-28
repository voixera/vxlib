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

        // 1. Fetch metadata from AniList (Metadata Only)
        $media = AniListService::detail($id);
        if (!$media || $media['media_type'] !== $type) {
            self::notFound();
        }

        // Search Query Candidates
        $queries = array_filter(array_unique([
            $media['title'] ?? '',
            $media['title_romaji'] ?? '',
            $media['alt_title'] ?? '',
        ]));

        // 2. Fetch Chapters using Providers (Prioritizing MangaDex)
        /** @var array<int, class-string<ChapterProvider>> $providers */
        $providers = [
            MangaDexService::class,
            FebryMangaApiService::class,
            MangaReaderApiService::class,
        ];

        $chapters = [];
        $activeProvider = null;

        $year = isset($media['year']) ? (int)$media['year'] : null;
        $author = $media['author'] ?? null;

        foreach ($providers as $providerClass) {
            try {
                $ch = $providerClass::findChapters($queries, $year, $author);
                if (!empty($ch)) {
                    $chapters = $ch;
                    $activeProvider = $providerClass;
                    break;
                }
            } catch (Throwable $e) {
                // Ignore & try next provider
            }
        }

        $selectedChapterId = $_GET['ch'] ?? null;
        $selectedChapterPages = [];
        $activeChIndex = -1;

        // ── Language selection (real, no fake translations) ──
        // Default to Bahasa Indonesia; fall back to English if no ID chapter exists.
        $reqLang = strtolower((string)($_GET['lang'] ?? 'id'));
        if ($reqLang !== 'id' && $reqLang !== 'en') {
            $reqLang = 'id';
        }
        $availableLangs = [];
        foreach ($chapters as $c) {
            if (!empty($c['language'])) {
                $availableLangs[strtolower((string)$c['language'])] = true;
            }
        }
        $availableLangs = array_keys($availableLangs);
        $activeLang = $reqLang;
        $indoUnavailable = false;
        if (!empty($chapters)) {
            $filtered = array_values(array_filter($chapters, function ($c) use ($activeLang) {
                return strtolower((string)($c['language'] ?? '')) === $activeLang;
            }));
            if ($filtered === [] && $activeLang === 'id' && in_array('en', $availableLangs, true)) {
                // No Indonesian chapter: fall back to English, surface a notice.
                $activeLang = 'en';
                $indoUnavailable = true;
                $filtered = array_values(array_filter($chapters, function ($c) {
                    return strtolower((string)($c['language'] ?? '')) === 'en';
                }));
            }
            if ($filtered !== []) {
                $chapters = $filtered;
            }
        }

        if (!empty($chapters)) {
            // Find or default to first chapter
            $activeChIndex = 0;
            if ($selectedChapterId !== null) {
                foreach ($chapters as $index => $c) {
                    if ($c['id'] === $selectedChapterId) {
                        $activeChIndex = $index;
                        break;
                    }
                }
            }

            $selectedChapterId = $chapters[$activeChIndex]['id'];
            $activeCh = $chapters[$activeChIndex];

            // Fetch actual pages from active provider
            try {
                if (($activeCh['source'] ?? '') === 'mangadex') {
                    $selectedChapterPages = MangaDexService::getPages($selectedChapterId);
                } elseif (($activeCh['source'] ?? '') === 'febry_manga') {
                    $selectedChapterPages = FebryMangaApiService::getPages($selectedChapterId);
                } else {
                    $selectedChapterPages = MangaReaderApiService::getPages($selectedChapterId);
                }
            } catch (Throwable $e) {
                $selectedChapterPages = [];
            }
        }

        // 3. Get User Reading Progress if logged in
        $user = Auth::user();
        $savedProgress = null;
        $localBookId = null;
        if ($user && SupabaseClient::configured()) {
            $catalogRepo = new CatalogRepository();
            $localBookId = $catalogRepo->ensureLocal($media);
            if ($localBookId !== null) {
                $libraryRepo = new LibraryRepository();
                $savedProgress = $libraryRepo->progress((int)$user['id'], $localBookId);
                // Touch reading history
                $libraryRepo->touchHistory((int)$user['id'], $localBookId);
            }
        }

        $prevChapter = $chapters[$activeChIndex - 1] ?? null;
        $nextChapter = $chapters[$activeChIndex + 1] ?? null;

        page('pages/media-reader', [
            'title' => 'Membaca ' . $media['title'] . ' — VoiXLib',
            'activeNav' => $type,
            'ogImage' => $media['cover_url'],
            'chromeless' => true,
        ], [
            'm' => $media,
            'bookId' => $localBookId,
            'chapters' => $chapters,
            'selectedChapterId' => $selectedChapterId,
            'selectedChapterPages' => $selectedChapterPages,
            'activeChIndex' => $activeChIndex,
            'prevChapter' => $prevChapter,
            'nextChapter' => $nextChapter,
            'savedProgress' => $savedProgress,
            'providerName' => $activeProvider ? $activeProvider::getProviderName() : null,
            'activeLang' => $activeLang ?? 'id',
            'availableLangs' => $availableLangs ?? [],
            'indoUnavailable' => $indoUnavailable ?? false,
        ]);
    }

    private static function notFound(): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => ''], ['message' => 'Judul ini tidak ada di katalog.']);
    }
}
