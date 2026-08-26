<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$db = new SupabaseClient();
$all = true;
foreach (['users', 'books', 'categories', 'user_library', 'bookmarks', 'reading_progress', 'reading_history'] as $t) {
    $r = $db->select($t, ['select' => '*', 'limit' => '1'], privileged: true);
    $ok = $r['ok'] || ($r['status'] === 200);
    if (!$ok) $all = false;
    echo str_pad($t, 18), $r['ok'] ? "OK" : "MISSING ({$r['status']})", "\n";
}
echo $all ? "SCHEMA READY — run: php tools/seed.php\n" : "schema not applied yet\n";
