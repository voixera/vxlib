<?php

declare(strict_types=1);

/**
 * Real admin surface. Every action is CSRF-checked and permission is derived
 * from the session's Discord ID against ADMIN_DISCORD_IDS — never from client state.
 */

final class AdminController
{
    public static function guard(): array
    {
        $user = Auth::requireUser();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            page('errors/403', ['title' => 'Forbidden — VoiXLib']);
        }
        return $user;
    }

    public static function dashboard(): void
    {
        self::guard();
        $notice = $_SESSION['admin_notice'] ?? null;
        unset($_SESSION['admin_notice']);

        $db = new SupabaseClient();
        $recent = $db->select('books', [
            'select' => 'id,title,author,external_id,featured,created_at',
            'order'  => 'created_at.desc',
            'limit'  => '30',
        ], privileged: true);

        page('pages/admin', [
            'title'       => 'Admin — VoiXLib',
            'description' => 'Catalog administration.',
            'activeNav'   => '',
            'scripts'     => ['admin.js'],
        ], [
            'notice'      => $notice,
            'books'       => $recent['rows'],
            'categories'  => BookRepository::CATEGORIES,
        ]);
    }

    /** Add a book from its Project Gutenberg ID. */
    public static function addGutenberg(): void
    {
        self::guard();
        Security::verifyCsrf();
        if (!RateLimiter::allow('admin_add', 20, 600)) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Too many additions in a short window. Wait a few minutes.'];
            redirect('/admin.php');
        }

        $gidRaw = trim((string)($_POST['gutenberg_id'] ?? ''));
        if (!ctype_digit($gidRaw) || (int)$gidRaw < 1) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Provide a numeric Gutenberg ID.'];
            redirect('/admin.php');
        }
        $gid = (int)$gidRaw;

        // Already present?
        $existing = (new SupabaseClient())->selectOne('books', ['select' => 'id', 'external_id' => 'eq.gutenberg:' . $gid], privileged: true);
        if ($existing) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => "Gutenberg #{$gid} is already in the catalog."];
            redirect('/admin.php');
        }

        $meta = GutenbergService::meta($gid);
        if (!$meta) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => "Could not fetch metadata for Gutenberg #{$gid}."];
            redirect('/admin.php');
        }

        $row = self::rowFromGutenbergMeta($meta);
        $res = (new SupabaseClient())->insert('books', $row);
        if (!$res['ok']) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'text' => 'Insert failed: ' . ($res['error'] ?? 'unknown error')];
            redirect('/admin.php');
        }
        $bookId = (int)($res['data'][0]['id'] ?? 0);

        self::assignCategories($bookId, (array)($_POST['categories'] ?? []), $meta['subjects'] ?? []);
        $_SESSION['admin_notice'] = ['type' => 'success', 'text' => 'Added “' . $row['title'] . '”.'];
        redirect('/admin.php');
    }

    public static function edit(): void
    {
        self::guard();
        Security::verifyCsrf();

        $bookId = filter_var($_POST['book_id'] ?? '', FILTER_VALIDATE_INT);
        if ($bookId === false || $bookId < 1) redirect('/admin.php');

        $changes = [];
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 300);
        if ($title !== '') $changes['title'] = $title;
        $author = mb_substr(trim((string)($_POST['author'] ?? '')), 0, 160);
        if ($author !== '') $changes['author'] = $author;
        $desc = trim((string)($_POST['description'] ?? ''));
        $changes['description'] = $desc === '' ? null : mb_substr($desc, 0, 4000);
        foreach ([
            'publication_year' => ['min_range' => -3000, 'max_range' => (int)date('Y')],
            'page_count'       => ['min_range' => 1, 'max_range' => 20000],
        ] as $field => $range) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $v = filter_var($_POST[$field], FILTER_VALIDATE_INT, ['options' => $range]);
                $changes[$field] = $v === false ? null : $v;
            }
        }
        $changes['featured'] = !empty($_POST['featured']);
        $changes['updated_at'] = gmdate('c');

        (new SupabaseClient())->update('books', ['id' => (string)$bookId], $changes);
        self::assignCategories($bookId, (array)($_POST['categories'] ?? []), []);
        $_SESSION['admin_notice'] = ['type' => 'success', 'text' => 'Saved changes to book #' . $bookId . '.'];
        redirect('/admin.php');
    }

    public static function delete(): void
    {
        self::guard();
        Security::verifyCsrf();
        $bookId = filter_var($_POST['book_id'] ?? '', FILTER_VALIDATE_INT);
        if ($bookId !== false && $bookId > 0) {
            $db = new SupabaseClient();
            $db->delete('book_categories', ['book_id' => (string)$bookId]);
            // Cascade removes user rows via FK ON DELETE CASCADE.
            $res = $db->delete('books', ['id' => (string)$bookId]);
            $_SESSION['admin_notice'] = $res['ok']
                ? ['type' => 'success', 'text' => 'Removed book #' . $bookId . '.']
                : ['type' => 'error', 'text' => 'Delete failed: ' . ($res['error'] ?? '?')];
        }
        redirect('/admin.php');
    }

    // ── internals ────────────────────────────────────────────────────

    private static function rowFromGutenbergMeta(array $meta): array
    {
        $gid   = (int)$meta['id'];
        $title = trim((string)($meta['title'] ?? 'Untitled'));
        $author = '';
        $life = null;
        if (!empty($meta['authors'][0]['name'])) {
            $author = (string)$meta['authors'][0]['name'];
            $b = $meta['authors'][0]['birth_year'] ?? null;
            $d = $meta['authors'][0]['death_year'] ?? null;
            if ($b || $d) $life = trim(($b ?? '?') . '–' . ($d ?? '?'), '–?');
        }

        $summary = '';
        foreach ((array)($meta['summaries'] ?? []) as $s) {
            if (is_string($s) && strlen($s) > 80) { $summary = trim($s); break; }
        }

        $cover = 'https://www.gutenberg.org/cache/epub/' . $gid . '/pg' . $gid . '.cover.medium.jpg';
        if (!OpenLibraryService::coverExists($cover)) {
            $ol = OpenLibraryService::enrich($title, $author ?: 'unknown');
            $cover = ($ol['cover_url'] ?? null) && OpenLibraryService::coverExists($ol['cover_url']) ? $ol['cover_url'] : null;
        }

        $subjects = implode(', ', array_slice(array_map('trim', (array)($meta['subjects'] ?? [])), 0, 8));

        return [
            'external_id'      => 'gutenberg:' . $gid,
            'source'           => 'gutenberg',
            'gutenberg_id'     => $gid,
            'title'            => mb_substr($title, 0, 300),
            'author'           => mb_substr($author !== '' ? $author : 'Unknown', 0, 160),
            'author_life'      => $life,
            'description'      => $summary !== '' ? mb_substr($summary, 0, 4000) : null,
            'cover_url'        => $cover,
            'source_url'       => 'https://www.gutenberg.org/ebooks/' . $gid,
            'read_url'         => 'https://www.gutenberg.org/cache/epub/' . $gid . '/pg' . $gid . '-images.html',
            'language'         => mb_substr((string)($meta['languages'][0] ?? 'en'), 0, 5),
            'publication_year' => null,
            'isbn'             => null,
            'downloads'        => (int)($meta['download_count'] ?? 0),
            'featured'         => false,
            'subjects'         => $subjects,
        ];
    }

    /** Replace the category set for a book; falls back to subject-based mapping. */
    private static function assignCategories(int $bookId, array $requestedSlugs, array $subjects): void
    {
        $validSlugs = array_column(BookRepository::CATEGORIES, 'slug');
        $slugs = [];
        foreach ($requestedSlugs as $slug) {
            if (in_array((string)$slug, $validSlugs, true)) $slugs[] = (string)$slug;
        }
        if (!$slugs && $subjects) {
            $hay = strtolower(implode(' ', array_map('strval', $subjects)));
            $map = [
                'detective' => 'mystery', 'mystery' => 'mystery',
                'science fiction' => 'science-fiction', 'fantasy' => 'fantasy',
                'horror' => 'gothic-horror', 'gothic' => 'gothic-horror',
                'romance' => 'romance', 'adventure' => 'adventure',
                'philosophy' => 'philosophy', 'history' => 'history',
                'short stories' => 'short-stories', 'poetry' => 'poetry',
                'classics' => 'classics', 'science' => 'nature-science',
            ];
            foreach ($map as $needle => $slug) {
                if (str_contains($hay, $needle)) $slugs[] = $slug;
            }
        }
        $slugs = array_slice(array_unique($slugs), 0, 2);

        $db = new SupabaseClient();
        $db->delete('book_categories', ['book_id' => (string)$bookId]);
        if (!$slugs) return;

        $cats = $db->select('categories', ['select' => 'id,slug'])['rows'];
        $idsBySlug = [];
        foreach ($cats as $c) $idsBySlug[(string)$c['slug']] = (int)$c['id'];

        $rows = [];
        foreach ($slugs as $slug) {
            if (isset($idsBySlug[$slug])) $rows[] = ['book_id' => $bookId, 'category_id' => $idsBySlug[$slug]];
        }
        if ($rows) $db->insert('book_categories', $rows);
    }
}
