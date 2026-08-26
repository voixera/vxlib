<?php

declare(strict_types=1);

/** Library shelves, bookmarks, reading progress, history. All user-scoped. */

final class LibraryRepository
{
    private SupabaseClient $db;

    public const STATUSES = ['want_to_read', 'reading', 'completed'];

    public function __construct(?SupabaseClient $client = null)
    {
        $this->db = $client ?? new SupabaseClient();
    }

    private function bookSelect(): string
    {
        return 'id,user_id,status,created_at,books('
            . 'id,external_id,gutenberg_id,title,author,cover_url,language,publication_year,read_url,source_url)';
    }

    /** @return array<int, array{status:string,book:array}> */
    public function libraryBooks(int $userId): array
    {
        if (!SupabaseClient::configured()) return [];
        $res = $this->db->select('user_library', [
            'select' => $this->bookSelect(),
            'user_id' => 'eq.' . $userId,
            'order'  => 'created_at.desc',
        ], privileged: true);

        $out = [];
        foreach ($res['rows'] as $row) {
            if (empty($row['books'])) continue;
            $out[] = [
                'status'   => (string)$row['status'],
                'added_at' => (string)$row['created_at'],
                'book'     => BookRepository::hydrate(array_merge($row['books'], ['categories' => []])),
            ];
        }
        return $out;
    }

    public function getStatus(int $userId, int $bookId): ?string
    {
        if (!SupabaseClient::configured()) return null;
        $row = $this->db->selectOne('user_library', [
            'select'  => 'status',
            'user_id' => 'eq.' . $userId,
            'book_id' => 'eq.' . $bookId,
        ], privileged: true);
        return $row['status'] ?? null;
    }

    public function setStatus(int $userId, int $bookId, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) return false;
        // Upsert on the (user_id, book_id) unique pair.
        return (bool)$this->db->insert('user_library', [
            'user_id' => $userId,
            'book_id' => $bookId,
            'status'  => $status,
        ], upsert: true, onConflict: 'user_id,book_id')['ok'];
    }

    public function remove(int $userId, int $bookId): bool
    {
        return (bool)$this->db->delete('user_library', ['user_id' => (string)$userId, 'book_id' => (string)$bookId])['ok'];
    }

    // ── Bookmarks ────────────────────────────────────────────────────

    public function bookmarks(int $userId, ?int $bookId = null): array
    {
        if (!SupabaseClient::configured()) return [];
        $query = [
            'select' => 'id,book_id,location,label,created_at,books(id,title,author,cover_url,external_id)',
            'user_id' => 'eq.' . $userId,
            'order'  => 'created_at.desc',
        ];
        if ($bookId) $query['book_id'] = 'eq.' . $bookId;
        return $this->db->select('bookmarks', $query, privileged: true)['rows'];
    }

    public function addBookmark(int $userId, int $bookId, string $location, ?string $label): bool
    {
        $location = mb_substr(trim($location), 0, 120);
        if ($location === '') return false;
        return (bool)$this->db->insert('bookmarks', [
            'user_id'  => $userId,
            'book_id'  => $bookId,
            'location' => $location,
            'label'    => $label !== null ? mb_substr($label, 0, 200) : null,
        ])['ok'];
    }

    public function removeBookmark(int $userId, int $bookmarkId): bool
    {
        return (bool)$this->db->delete('bookmarks', ['user_id' => (string)$userId, 'id' => (string)$bookmarkId])['ok'];
    }

    // ── Reading progress ─────────────────────────────────────────────

    /** @return array{progress:int,chapter:int,location:?string,updated_at:string}|null */
    public function progress(int $userId, int $bookId): ?array
    {
        if (!SupabaseClient::configured()) return null;
        $row = $this->db->selectOne('reading_progress', [
            'select'  => 'progress,chapter,location,updated_at',
            'user_id' => 'eq.' . $userId,
            'book_id' => 'eq.' . $bookId,
        ], privileged: true);
        return $row ?: null;
    }

    public function saveProgress(int $userId, int $bookId, int $progress, int $chapter, ?string $location): bool
    {
        $progress = max(0, min(100, $progress));
        return (bool)$this->db->insert('reading_progress', [
            'user_id'   => $userId,
            'book_id'   => $bookId,
            'progress'  => $progress,
            'chapter'   => max(0, $chapter),
            'location'  => $location !== null ? mb_substr($location, 0, 120) : null,
            'updated_at' => gmdate('c'),
        ], upsert: true, onConflict: 'user_id,book_id')['ok'];
    }

    /** Continue-reading shelf across all books. */
    public function recentProgress(int $userId, int $limit = 8): array
    {
        if (!SupabaseClient::configured()) return [];
        $res = $this->db->select('reading_progress', [
            'select' => 'book_id,progress,chapter,updated_at,books(id,external_id,gutenberg_id,title,author,cover_url,read_url)',
            'user_id' => 'eq.' . $userId,
            'order'  => 'updated_at.desc',
            'limit'  => (string)$limit,
        ], privileged: true);
        $out = [];
        foreach ($res['rows'] as $row) {
            if (empty($row['books'])) continue;
            $b = BookRepository::hydrate(array_merge($row['books'], ['categories' => []]));
            $b['progress_pct'] = (int)$row['progress'];
            $out[] = $b;
        }
        return $out;
    }

    // ── History ──────────────────────────────────────────────────────

    public function touchHistory(int $userId, int $bookId): void
    {
        $this->db->insert('reading_history', [
            'user_id'        => $userId,
            'book_id'        => $bookId,
            'last_opened_at' => gmdate('c'),
        ], upsert: true, onConflict: 'user_id,book_id');
    }

    public function history(int $userId, int $limit = 12): array
    {
        if (!SupabaseClient::configured()) return [];
        $res = $this->db->select('reading_history', [
            'select' => 'last_opened_at,books(id,external_id,gutenberg_id,title,author,cover_url,language,publication_year,read_url,source_url)',
            'user_id' => 'eq.' . $userId,
            'order'  => 'last_opened_at.desc',
            'limit'  => (string)$limit,
        ], privileged: true);
        $out = [];
        foreach ($res['rows'] as $row) {
            if (empty($row['books'])) continue;
            $out[] = [
                'opened_at' => (string)$row['last_opened_at'],
                'book'      => BookRepository::hydrate(array_merge($row['books'], ['categories' => []])),
            ];
        }
        return $out;
    }

    public function countCompleted(int $userId): int
    {
        if (!SupabaseClient::configured()) return 0;
        $res = $this->db->select('user_library', ['select' => 'id', 'status' => 'eq.completed', 'user_id' => 'eq.' . $userId], withCount: true, privileged: true);
        return $res['total'] ?? 0;
    }

    public function countReading(int $userId): int
    {
        if (!SupabaseClient::configured()) return 0;
        $res = $this->db->select('user_library', ['select' => 'id', 'status' => 'eq.reading', 'user_id' => 'eq.' . $userId], withCount: true, privileged: true);
        return $res['total'] ?? 0;
    }
}

