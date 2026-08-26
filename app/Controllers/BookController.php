<?php

declare(strict_types=1);

/** Book detail + reader pages. */

final class BookController
{
    public static function show(): void
    {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '' || !preg_match('/^[0-9A-Za-z:_\-]{1,64}$/', $id)) {
            self::notFound('That book reference looks malformed.');
        }

        $book = (new BookRepository())->find($id);
        if (!$book) {
            self::notFound('We could not find that title in the catalog.');
        }

        $book = self::lazyEnrich($book);

        $user = Auth::user();
        $status = null;
        $bookmarked = false;
        $progress = null;
        if ($user) {
            $library = new LibraryRepository();
            $status = $library->getStatus((int)$user['id'], $book['id']);
            $progress = $library->progress((int)$user['id'], $book['id']);
            $bookmarked = (bool)array_filter(
                $library->bookmarks((int)$user['id'], $book['id']),
                fn($b) => ($b['location'] ?? '') !== ''
            );
        } else {
            // Anonymous visitors may still have a local progress marker.
            $bookmarked = false;
        }

        page('pages/book', [
            'title'       => 'VoiXLib — ' . $book['title'],
            'description' => mb_substr(strip_tags($book['description'] ?? ''), 0, 160) ?: ('Read ' . $book['title'] . ' by ' . $book['author'] . ' on VoiXLib.'),
            'activeNav'   => 'explore',
            'ogType'      => 'book',
            'scripts'     => ['book.js'],
        ], [
            'book'        => $book,
            'user'        => $user,
            'shelfStatus' => $status,
            'hasBookmark' => $bookmarked,
            'progress'    => $progress,
        ]);
    }

    public static function reader(): void
    {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '' || !preg_match('/^[0-9A-Za-z:_\-]{1,64}$/', $id)) {
            http_response_code(404);
            page('errors/404', ['title' => 'Not found — VoiXLib', 'message' => 'This shelf doesn’t exist.']);
        }

        $book = (new BookRepository())->find($id);
        if (!$book) {
            http_response_code(404);
            page('errors/404', ['title' => 'Not found — VoiXLib', 'message' => 'This shelf doesn’t exist.']);
        }
        if (!$book['readable']) {
            page('pages/reader-unavailable', [
                'title'       => 'Reading unavailable — VoiXLib',
                'description' => 'This book cannot be read in-browser.',
                'book'        => $book,
            ]);
        }

        // Record history for signed-in readers.
        if (Auth::check()) {
            (new LibraryRepository())->touchHistory(Auth::id(), $book['id']);
        }

        $user = Auth::user();
        page('pages/reader', [
            'title'    => 'Reading — ' . $book['title'],
            'description' => 'Reader view for ' . $book['title'] . ' by ' . $book['author'] . '.',
            'chromeless' => true,
            'scripts'  => ['reader.js'],
            'book'     => $book,
            'user'     => $user,
        ], ['book' => $book]);
    }

    private static function notFound(string $message): never
    {
        http_response_code(404);
        page('errors/404', ['title' => 'Book not found — VoiXLib', 'message' => $message]);
    }

    /**
     * Progressive enrichment: when the catalog row lacks year/pages/isbn,
     * ask Open Library once (cached) and persist the result. Silent on failure.
     */
    private static function lazyEnrich(array $book): array
    {
        if (!SupabaseClient::configured() || $book['source_url'] === '') return $book;
        $needs = !$book['publication_year'] || !$book['page_count'] || !$book['isbn'];
        if (!$needs) return $book;

        try {
            $enriched = Cache::remember('enrich:' . md5($book['title'] . '|' . $book['author']), 604800, function () use ($book) {
                $r = OpenLibraryService::enrich($book['title'], $book['author']);
                return $r ?: [];
            });
            if (!$enriched) return $book;

            $changes = [];
            foreach (['publication_year', 'page_count', 'isbn'] as $field) {
                if (!$book[$field] && !empty($enriched[$field])) {
                    // Only accept plausible years for public-domain-era works.
                    if ($field === 'publication_year' && ($enriched[$field] > 1970)) continue;
                    $changes[$field] = $enriched[$field];
                }
            }
            if (!$book['description'] && !empty($enriched['description'])) {
                $changes['description'] = mb_substr((string)$enriched['description'], 0, 4000);
            }
            if (!$changes) return $book;

            $changes['updated_at'] = gmdate('c');
            (new SupabaseClient())->update('books', ['id' => (string)$book['id']], $changes);
            return array_merge($book, $changes);
        } catch (\Throwable $e) {
            return $book; // enrichment is best-effort garnish, never fatal
        }
    }
}

