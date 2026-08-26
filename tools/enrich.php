<?php
/**
 * Backfill publication_year / page_count / isbn for catalog rows that lack them,
 * using Open Library (gentle pacing to respect its rate limits).
 * Usage: php tools/enrich.php [maxBooks]
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!SupabaseClient::configured()) { echo "Configure Supabase first.\n"; exit(1); }

$max = max(1, (int)($argv[1] ?? 40));
$db = new SupabaseClient();

$res = $db->select('books', [
    'select' => 'id,title,author,publication_year,page_count,isbn',
    'publication_year' => 'is.null',
    'order' => 'downloads.desc',
    'limit' => (string)$max,
], privileged: true);

$books = $res['rows'];
echo count($books) . " books to enrich\n";

$done = 0;
foreach ($books as $b) {
    $r = OpenLibraryService::enrich((string)$b['title'], (string)$b['author']);
    if (!$r || empty($r['first_publish_year']) || $r['first_publish_year'] > 1970) {
        // Record the miss so we don't re-query constantly.
        Cache::set('enrich:miss:' . md5(strtolower((string)$b['title']) . '|' . strtolower((string)$b['author'])), 1, 86400);
        echo '  · ', mb_substr((string)$b['title'], 0, 44), "\n";
        usleep(1200000);
        continue;
    }
    $changes = ['publication_year' => (int)$r['first_publish_year']];
    if (!$b['page_count'] && !empty($r['page_count'])) $changes['page_count'] = (int)$r['page_count'];
    if (!$b['isbn'] && !empty($r['isbn'])) $changes['isbn'] = $r['isbn'];
    $changes['updated_at'] = gmdate('c');
    $db->update('books', ['id' => (string)$b['id']], $changes);
    $done++;
    echo '  ✓ ', mb_substr((string)$b['title'], 0, 44), ' → ', $changes['publication_year'], "\n";
    usleep(1200000);
}
echo "enriched {$done}/" . count($books) . "\n";
