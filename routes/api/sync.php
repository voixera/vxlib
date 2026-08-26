<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/**
 * One-time local→account sync after Discord login.
 * Client posts localStorage-backed progress/bookmarks/prefs; server merges
 * without clobbering better server values (max progress wins, prefs merged).
 */

Security::bootSession();
$user = Auth::user();
if (!$user) json_response(['error' => 'auth_required'], 401);
Security::verifyCsrf();
if (!RateLimiter::allow('api_sync', 5, 300)) json_response(['error' => 'rate_limited'], 429);

$in = json_input();
$repo = new LibraryRepository();
$userId = (int)$user['id'];
$merged = ['progress' => 0, 'bookmarks' => 0];

// Progress entries: [{book_id, progress, chapter, location}]
foreach ((array)($in['progress'] ?? []) as $entry) {
    $bookId = filter_var($entry['book_id'] ?? '', FILTER_VALIDATE_INT);
    $pct    = filter_var($entry['progress'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
    if ($bookId === false || $pct === false) continue;
    if (!is_numeric($entry['chapter'] ?? null)) continue;
    $chapter = (int)$entry['chapter'];
    if ($chapter < 0 || $chapter > 10000) continue;
    $location = isset($entry['location']) ? mb_substr(trim((string)$entry['location']), 0, 100) : null;

    $existing = $repo->progress($userId, $bookId);
    if ($existing && (int)$existing['progress'] >= $pct) continue; // server already ahead

    if ($repo->saveProgress($userId, $bookId, $pct, $chapter, $location ?: null)) {
        $merged['progress']++;
    }
}

// Bookmarks: [{book_id, location, label}] — skip duplicates by (book_id, location)
$existingMarks = $repo->bookmarks($userId);
$seenKeys = [];
foreach ($existingMarks as $m) {
    $seenKeys[$m['book_id'] . '|' . ($m['location'] ?? '')] = true;
}
foreach ((array)($in['bookmarks'] ?? []) as $entry) {
    $bookId = filter_var($entry['book_id'] ?? '', FILTER_VALIDATE_INT);
    $locRaw = isset($entry['location']) ? mb_substr(trim((string)$entry['location']), 0, 120) : '';
    if ($bookId === false || $locRaw === '' || isset($seenKeys[$bookId . '|' . $locRaw])) continue;
    if ($repo->addBookmark($userId, $bookId, $locRaw, isset($entry['label']) ? mb_substr((string)$entry['label'], 0, 200) : null)) {
        $seenKeys[$bookId . '|' . $locRaw] = true;
        $merged['bookmarks']++;
    }
}

// Prefs merge (client wins for keys it sends).
$prefs = is_array($in['prefs'] ?? null) ? Prefs::sanitizeArray($in['prefs']) : [];
if ($prefs) {
    $current = is_array($user['prefs'] ?? null) ? $user['prefs'] : [];
    UserRepository::savePrefsStatic($userId, array_merge($current, $prefs));
}

json_response(['ok' => true, 'merged' => $merged]);
