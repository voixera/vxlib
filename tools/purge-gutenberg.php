<?php

/**
 * Remove the Project Gutenberg catalog (source = gutenberg) from Supabase.
 * Junction rows (book_categories) cascade automatically; user library rows
 * reference books with on delete cascade as well.
 *
 * Usage: php tools/purge-gutenberg.php
 * Needs SUPABASE_SERVICE_ROLE_KEY in .env.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') { echo "CLI only\n"; exit(1); }
if (!SupabaseClient::configured()) { echo "Set SUPABASE_URL and SUPABASE_ANON_KEY in .env first.\n"; exit(1); }
if (!Config::get('SUPABASE_SERVICE_ROLE_KEY')) { echo "SUPABASE_SERVICE_ROLE_KEY is required for deletion.\n"; exit(1); }

$db = new SupabaseClient();

$res = $db->select('books', [
    'select' => 'id,external_id',
    'source' => 'eq.gutenberg',
], privileged: true, withCount: true);

$rows = $res['rows'] ?? [];
$total = $res['total'] ?? count($rows);
echo "Found {$total} Gutenberg books to delete.\n";

$deleted = 0;
foreach ($rows as $row) {
    $out = $db->delete('books', ['id' => (string)$row['id']]);
    if (!$out['ok']) {
        echo "  fail {$row['external_id']}: " . ($out['error'] ?? '?') . "\n";
        continue;
    }
    $deleted++;
    if ($deleted % 20 === 0) echo "  {$deleted}/{$total}\n";
    usleep(60000);
}

echo "done — deleted: {$deleted}\n";
