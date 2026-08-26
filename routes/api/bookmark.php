<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** Bookmark mutations. POST JSON {action: add|remove|list, book_id, location, label, bookmark_id}. */

Security::bootSession();
$user = Auth::user();
if (!$user) json_response(['error' => 'auth_required'], 401);
Security::verifyCsrf();
if (!RateLimiter::allow('api_bookmark', 90, 60)) json_response(['error' => 'rate_limited'], 429);

$in     = json_input() ?: $_POST;
$action = (string)($in['action'] ?? 'add');
$repo   = new LibraryRepository();

switch ($action) {
    case 'add':
        $bookId = filter_var($in['book_id'] ?? '', FILTER_VALIDATE_INT);
        $location = trim((string)($in['location'] ?? ''));
        if ($bookId === false || $bookId < 1 || $location === '') {
            json_response(['error' => 'invalid_input'], 422);
        }
        $ok = $repo->addBookmark((int)$user['id'], $bookId, $location, ($in['label'] ?? null) !== null ? (string)$in['label'] : null);
        json_response(['ok' => $ok], $ok ? 200 : 502);

    case 'remove':
        $bookmarkId = filter_var($in['bookmark_id'] ?? '', FILTER_VALIDATE_INT);
        if ($bookmarkId === false || $bookmarkId < 1) json_response(['error' => 'invalid_input'], 422);
        $ok = $repo->removeBookmark((int)$user['id'], $bookmarkId);
        json_response(['ok' => $ok], $ok ? 200 : 502);

    case 'list':
        $bookId = isset($in['book_id']) ? filter_var($in['book_id'], FILTER_VALIDATE_INT) : null;
        json_response(['bookmarks' => $repo->bookmarks((int)$user['id'], $bookId !== false && $bookId ? $bookId : null)]);

    default:
        json_response(['error' => 'unknown_action'], 422);
}
