<?php
/* Remove the integration-test user and its rows (dev cleanup). */
require dirname(__DIR__) . '/app/bootstrap.php';

$db = new SupabaseClient();
$res = $db->delete('users', ['discord_id' => '123456789012345678']);
echo $res['ok'] ? "test user removed (cascades)\n" : ('failed: ' . ($res['error'] ?? '?') . "\n");
