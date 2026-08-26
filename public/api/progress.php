<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/**
 * Reading progress.
 *   GET  ?book_id=ID            → saved progress (auth)
 *   POST {book_id, progress, chapter, location} → upsert (auth + CSRF)
 */

Security::bootSession();
$user = Auth::user();
if (!$user) json_response(['error' => 'auth_required'], 401);

$repo = new LibraryRepository();

if (!is_post()) {
    $bookId = filter_var($_GET['book_id'] ?? '', FILTER_VALIDATE_INT);
    if ($bookId === false || $bookId < 1) json_response(['error' => 'invalid_input'], 422);
    json_response(['progress' => $repo->progress((int)$user['id'], $bookId)]);
}

Security::verifyCsrf();
if (!RateLimiter::allow('api_progress', 120, 60)) json_response(['error' => 'rate_limited'], 429);

$in = json_input() ?: $_POST;
$bookId  = filter_var($in['book_id'] ?? '', FILTER_VALIDATE_INT);
$progress = filter_var($in['progress'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$chapter  = filter_var($in['chapter'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]);
if ($bookId === false || $bookId < 1 || $progress === false || $chapter === false) {
    json_response(['error' => 'invalid_input'], 422);
}
$location = isset($in['location']) ? mb_substr(trim((string)$in['location']), 0, 100) : null;

$ok = $repo->saveProgress((int)$user['id'], $bookId, $progress, $chapter, $location !== '' ? $location : null);
json_response(['ok' => $ok], $ok ? 200 : 502);
