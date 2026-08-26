<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** Library shelf mutations. POST JSON {action, book_id, status}. Auth + CSRF required. */

Security::bootSession();
$user = Auth::user();
if (!$user) json_response(['error' => 'auth_required'], 401);
Security::verifyCsrf();
if (!RateLimiter::allow('api_library', 60, 60)) json_response(['error' => 'rate_limited'], 429);

$in    = json_input() ?: $_POST;
$action = (string)($in['action'] ?? '');
$bookId = filter_var($in['book_id'] ?? '', FILTER_VALIDATE_INT);
if ($bookId === false || $bookId < 1) json_response(['error' => 'invalid_book'], 422);

// Book must exist in the public catalog.
$repo = new LibraryRepository();

switch ($action) {
    case 'add':
        $status = (string)($in['status'] ?? 'want_to_read');
        if (!in_array($status, $repo::STATUSES, true)) $status = 'want_to_read';
        $ok = $repo->setStatus((int)$user['id'], $bookId, $status);
        json_response(['ok' => $ok, 'status' => $ok ? $status : null], $ok ? 200 : 502);

    case 'remove':
        $ok = $repo->remove((int)$user['id'], $bookId);
        json_response(['ok' => $ok], $ok ? 200 : 502);

    default:
        json_response(['error' => 'unknown_action'], 422);
}
