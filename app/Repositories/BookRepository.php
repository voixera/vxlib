<?php

declare(strict_types=1);

/** Data access for the public book catalog (read-only, anon key). */

final class BookRepository
{
    private SupabaseClient $db;

    /** Curated VoiXLib categories (static configuration, not scraped claims). */
    public const CATEGORIES = [
        ['name' => 'Classics', 'slug' => 'classics'],
        ['name' => 'Mystery & Detective', 'slug' => 'mystery'],
        ['name' => 'Science Fiction', 'slug' => 'science-fiction'],
        ['name' => 'Fantasy', 'slug' => 'fantasy'],
        ['name' => 'Romance', 'slug' => 'romance'],
        ['name' => 'Gothic & Horror', 'slug' => 'gothic-horror'],
        ['name' => 'Adventure', 'slug' => 'adventure'],
        ['name' => 'Philosophy', 'slug' => 'philosophy'],
        ['name' => 'History', 'slug' => 'history'],
        ['name' => 'Short Stories', 'slug' => 'short-stories'],
        ['name' => 'Poetry', 'slug' => 'poetry'],
        ['name' => 'Nature & Science', 'slug' => 'nature-science'],
        ['name' => 'Manga', 'slug' => 'manga'],
        ['name' => 'Manhwa', 'slug' => 'manhwa'],
        ['name' => 'Manhua', 'slug' => 'manhua'],
        ['name' => 'Anime', 'slug' => 'anime'],
        ['name' => 'Light Novel', 'slug' => 'light-novel'],
        ['name' => 'Webtoon', 'slug' => 'webtoon'],
        ['name' => 'Doujinshi', 'slug' => 'doujinshi'],
        ['name' => 'Novel Indonesia', 'slug' => 'novel-indonesia'],
        ['name' => 'Hentai', 'slug' => 'hentai'],
    ];

    public function __construct(?SupabaseClient $client = null)
    {
        $this->db = $client ?? new SupabaseClient();
    }

    /**
     * Query the catalog.
     * @param array{q?:string,category?:string,language?:string,year_from?:int,year_to?:int,
     *              sort?:string,page?:int,per_page?:int,featured?:bool,ids?:string[]}|array{} $params
     * @return array{books:array,total:?int,error:?string}
     */
    public function search(array $params): array
    {
        if (!SupabaseClient::configured()) {
            return ['books' => [], 'total' => null, 'error' => 'supabase_not_configured'];
        }

        $perPage = min(max((int)($params['per_page'] ?? 24), 1), 48);
        $page    = max((int)($params['page'] ?? 1), 1);
        $from    = ($page - 1) * $perPage;
        $to      = $from + $perPage - 1;

        // PostgREST auto-detects the books<->categories m2m through book_categories.
        // !inner makes a category filter actually constrain rows.
        $catEmbed = empty($params['category']) ? 'categories(name,slug)' : 'categories!inner(name,slug)';
        $select = 'id,source,external_id,gutenberg_id,title,author,author_life,description,cover_url,source_url,'
            . 'read_url,language,publication_year,page_count,isbn,downloads,featured,subjects,'
            . $catEmbed;

        $query = [
            'select' => $select,
            'order'  => self::orderClause($params['sort'] ?? 'relevance'),
            'limit'  => (string)$perPage,
            'offset' => (string)$from,
        ];

        $q = trim((string)($params['q'] ?? ''));
        if ($q !== '') {
            // Escape PostgREST pattern metacharacters.
            $safe = str_replace([',', '(', ')', '"'], ' ', $q);
            $query['or'] = sprintf(
                '(title.ilike."*%s*",author.ilike."*%s*",subjects.ilike."*%s*",description.ilike."*%s*")',
                $safe, $safe, $safe, $safe
            );
        }
        if (!empty($params['category'])) {
            $query['categories.slug'] = 'eq.' . $params['category'];
        }
        if (!empty($params['language'])) {
            $query['language'] = 'eq.' . substr($params['language'], 0, 5);
        }
        if (!empty($params['year_from'])) {
            $query['publication_year'] = 'gte.' . (int)$params['year_from'];
        }
        if (!empty($params['year_to']) && empty($params['year_from'])) {
            $query['publication_year'] = 'lte.' . (int)$params['year_to'];
        } elseif (!empty($params['year_to'])) {
            // Range queries need and= when both bounds present.
            unset($query['publication_year']);
            $query['and'] = sprintf('(publication_year.gte.%d,publication_year.lte.%d)',
                (int)$params['year_from'], (int)$params['year_to']);
        }
        if (!empty($params['featured'])) {
            $query['featured'] = 'is.true';
        }
        if (!empty($params['ids']) && is_array($params['ids'])) {
            $clean = array_filter(array_map('strval', $params['ids']), fn($i) => preg_match('/^[0-9a-z:_\-]{1,64}$/i', $i));
            if ($clean) $query['external_id'] = 'in.(' . implode(',', $clean) . ')';
        }

        $res = $this->db->select('books', $query, privileged: false, withCount: true);
        if (!$res['ok']) {
            return ['books' => [], 'total' => null, 'error' => $res['error'] ?? 'catalog_unavailable'];
        }

        return [
            'books' => array_map([self::class, 'hydrate'], $res['rows']),
            'total' => $res['total'],
            'error' => null,
        ];
    }

    public function find(string|int $idOrExternal): ?array
    {
        if (!SupabaseClient::configured()) return null;

        $col = ctype_digit((string)$idOrExternal) ? 'id' : 'external_id';
        $row = $this->db->selectOne('books', [
            'select' => 'id,source,external_id,gutenberg_id,title,author,author_life,description,cover_url,source_url,'
                . 'read_url,language,publication_year,page_count,isbn,downloads,featured,subjects,created_at,updated_at',
            $col => 'eq.' . $idOrExternal,
        ]);
        return $row ? self::hydrate($row) : null;
    }

    public function countAll(): ?int
    {
        if (!SupabaseClient::configured()) return null;
        $res = $this->db->select('books', ['select' => 'id', 'limit' => '1'], withCount: true);
        return $res['total'];
    }

    /** Normalize a raw row into view-friendly shape. */
    public static function hydrate(array $row): array
    {
        $cats = [];
        foreach ((array)($row['categories'] ?? []) as $c) {
            if (is_array($c) && isset($c['name'], $c['slug'])) {
                $cats[] = ['name' => (string)$c['name'], 'slug' => (string)$c['slug']];
            }
        }
        return [
            'id'               => (int)$row['id'],
            'external_id'      => (string)($row['external_id'] ?? ''),
            'source'           => (string)($row['source'] ?? 'gutenberg'),
            'gutenberg_id'     => isset($row['gutenberg_id']) ? (int)$row['gutenberg_id'] : null,
            'title'            => (string)($row['title'] ?? ''),
            'author'           => (string)($row['author'] ?? 'Unknown'),
            'author_life'      => isset($row['author_life']) ? (string)$row['author_life'] : null,
            'description'      => isset($row['description']) && $row['description'] !== '' ? (string)$row['description'] : null,
            'cover_url'        => !empty($row['cover_url']) ? (string)$row['cover_url'] : null,
            'source_url'       => (string)($row['source_url'] ?? ''),
            'read_url'         => !empty($row['read_url']) ? (string)$row['read_url'] : null,
            'language'         => (string)($row['language'] ?? 'en'),
            'publication_year' => isset($row['publication_year']) ? (int)$row['publication_year'] : null,
            'page_count'       => isset($row['page_count']) ? (int)$row['page_count'] : null,
            'isbn'             => isset($row['isbn']) && $row['isbn'] !== '' ? (string)$row['isbn'] : null,
            'downloads'        => (int)($row['downloads'] ?? 0),
            'featured'         => (bool)($row['featured'] ?? false),
            'subjects'         => (string)($row['subjects'] ?? ''),
            'categories'       => $cats,
            'readable'         => !empty($row['gutenberg_id']) || ($row['source'] ?? '') === 'voixlib',
        ];
    }

    /** Trim a hydrated book down to what catalog cards need. */
    public static function hydratePublic(array $b): array
    {
        return [
            'id'          => $b['id'],
            'external_id' => $b['external_id'],
            'title'       => $b['title'],
            'author'      => $b['author'],
            'cover_url'   => $b['cover_url'],
            'language'    => $b['language'],
            'year'        => $b['publication_year'],
            'readable'    => $b['readable'],
            'categories'  => array_slice($b['categories'], 0, 1),
            'excerpt'     => $b['description'] !== null ? mb_substr($b['description'], 0, 180) : null,
        ];
    }

    private static function orderClause(string $sort): string
    {
        return match ($sort) {
            'title_asc'  => 'title.asc',
            'title_desc' => 'title.desc',
            'newest'     => 'id.desc',
            'oldest'     => 'publication_year.asc.nullslast,title.asc',
            'popular'    => 'downloads.desc,title.asc',
            default      => 'downloads.desc,title.asc', // relevance ≈ popularity until FTS exists
        };
    }
}

