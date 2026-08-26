<?php

/**
 * VoiXLib seeder — loads storage/seed/books.json (real Project Gutenberg
 * metadata, pre-fetched by tools/build-seed.php) into Supabase.
 *
 * Usage:  php tools/seed.php
 * Needs:  SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY in .env, and the schema
 *         from supabase/schema.sql applied first.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') { echo "CLI only\n"; exit(1); }
if (!SupabaseClient::configured()) { echo "Set SUPABASE_URL and SUPABASE_ANON_KEY in .env first.\n"; exit(1); }
if (!Config::get('SUPABASE_SERVICE_ROLE_KEY')) { echo "SUPABASE_SERVICE_ROLE_KEY is required for seeding.\n"; exit(1); }

// ── VoiXLib-native catalog: comics (SVG pages) + Bahasa Indonesia novels ──
$files = ['manga.json', 'novels-id.json'];
$books = [];
foreach ($files as $f) {
    $path = dirname(__DIR__) . '/storage/seed/' . $f;
    if (!is_file($path)) {
        echo "Missing {$path}\n";
        exit(1);
    }
    $payload = json_decode((string)file_get_contents($path), true);
    $books = array_merge($books, $payload['books'] ?? []);
}
if (!$books) { echo "Seed files contain no books.\n"; exit(1); }

$db = new SupabaseClient();
echo 'Seeding ' . count($books) . " books...\n";

// ── Categories ──────────────────────────────────────────────
$catRows = BookRepository::CATEGORIES;
$res = $db->insert('categories', array_map(fn($c) => ['name' => $c['name'], 'slug' => $c['slug']], $catRows), upsert: true, onConflict: 'slug');
$cats = $db->select('categories', ['select' => 'id,slug'])['rows'] ?? [];
$catIdsBySlug = [];
foreach ($cats as $c) $catIdsBySlug[$c['slug']] = (int)$c['id'];
echo count($catIdsBySlug) . " categories ready\n";

// ── Books (upsert on external_id) ───────────────────────────
$inserted = $updated = $failed = $linked = 0;
$bcBuffer = [];

foreach ($books as $i => $b) {
    $row = [
        'external_id'      => (string)$b['external_id'],
        'source'           => (string)($b['source'] ?? 'gutenberg'),
        'gutenberg_id'     => str_starts_with((string)$b['external_id'], 'gutenberg:')
            ? (int)substr((string)$b['external_id'], strpos((string)$b['external_id'], ':') + 1)
            : null,
        'title'            => mb_substr((string)$b['title'], 0, 300),
        'author'           => mb_substr((string)$b['author'], 0, 160),
        'author_life'      => $b['author_life'] ?? null,
        'description'      => $b['description'] ?? null,
        'cover_url'        => $b['cover_url'] ?? null,
        'source_url'       => (string)$b['source_url'],
        'read_url'         => $b['read_url'] ?? null,
        'language'         => mb_substr((string)($b['language'] ?? 'en'), 0, 5),
        'publication_year' => $b['publication_year'] ?? null,
        'page_count'       => $b['page_count'] ?? null,
        'isbn'             => $b['isbn'] ?? null,
        'downloads'        => (int)($b['downloads'] ?? 0),
        'subjects'         => implode(', ', array_map('strval', (array)($b['subjects'] ?? []))),
    ];

    // gutenberg_id must be a clean int or null
    if (!is_int($row['gutenberg_id'])) {
        preg_match('/gutenberg:(\d+)/', (string)$row['external_id'], $m);
        $row['gutenberg_id'] = isset($m[1]) ? (int)$m[1] : null;
    }

    $res = $db->insert('books', $row, upsert: true, onConflict: 'external_id');
    if (!$res['ok']) { $failed++; if ($failed <= 3) echo '  fail: ' . ($res['error'] ?? '?') . "\n"; continue; }

    $bookId = (int)($res['data'][0]['id'] ?? 0);
    if (!$bookId) {
        // merge-duplicates may omit representation; look it up
        $found = $db->selectOne('books', ['select' => 'id', 'external_id' => 'eq.' . $row['external_id']], privileged: true);
        $bookId = $found ? (int)$found['id'] : 0;
    } else {
        $inserted++;
    }

    if ($bookId) {
        foreach ((array)($b['categories'] ?? []) as $catName) {
            foreach (BookRepository::CATEGORIES as $def) {
                if ($def['name'] === $catName && isset($catIdsBySlug[$def['slug']])) {
                    $bcBuffer[] = ['book_id' => $bookId, 'category_id' => $catIdsBySlug[$def['slug']]];
                    $linked++;
                }
            }
        }
    }
    if (($i + 1) % 40 === 0) echo '  ' . ($i + 1) . '/' . count($books) . "\n";
    usleep(60000); // stay gentle with the REST layer
}

// de-dupe junction rows before insert
$bcUnique = [];
foreach ($bcBuffer as $r) $bcUnique[$r['book_id'] . ':' . $r['category_id']] = $r;
foreach (array_chunk(array_values($bcUnique), 200) as $chunk) {
    $db->insert('book_categories', $chunk, upsert: true, onConflict: 'book_id,category_id');
}
echo "category links: {$linked}\n";
echo "done — new/updated: {$inserted}, failures: {$failed}\n";
