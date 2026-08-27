<?php

declare(strict_types=1);

/** /book.php?id=… — tautan lama diarahkan ke halaman detail baru. */

require_once dirname(__DIR__) . '/app/bootstrap.php';

Security::bootSession();

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '' || !preg_match('/^[0-9A-Za-z:_\-]{1,64}$/', $id)) {
    redirect('/explore.php');
}

if (preg_match('/^anilist:(\d+)$/', $id, $m)) {
    $row = SupabaseClient::configured()
        ? (new SupabaseClient())->selectOne('books', ['select' => 'media_type', 'external_id' => 'eq.' . $id], privileged: true)
        : null;
    $type = $row['media_type'] ?? 'manga';
    $type = in_array($type, ['manga', 'manhwa'], true) ? $type : 'manga';
    redirect('/detail/' . $type . '/' . $m[1]);
}

redirect('/');
