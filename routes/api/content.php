<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/**
 * Reader content proxy: GET ?id=<external_id|numeric book id>
 * Returns sanitized, chapter-split Gutenberg HTML (cached server-side).
 * Only serves catalog books whose source is gutenberg.org.
 */

Security::bootSession();

if (!RateLimiter::allow('api_content', 60, 60)) json_response(['error' => 'rate_limited'], 429);

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '' || !preg_match('/^[0-9A-Za-z:_\-]{1,64}$/', $id)) {
    json_response(['error' => 'invalid_id'], 422);
}

$book = (new BookRepository())->find($id);
if (!$book) json_response(['error' => 'not_found'], 404);

if ($book['gutenberg_id'] === null || $book['read_url'] === null || !str_contains((string)$book['read_url'], 'https://www.gutenberg.org/')) {
    json_response(['error' => 'not_readable', 'source_url' => $book['source_url']], 415);
}

$content = Cache::remember(
    'reader:' . $book['external_id'],
    604800,
    fn() => GutenbergService::readableContent($book['read_url'], $book['gutenberg_id'])
);

if (!$content || empty($content['chapters'])) {
    json_response([
        'error'      => 'content_unavailable',
        'message'    => 'The reading copy could not be retrieved right now.',
        'source_url' => $book['source_url'],
    ], 502);
}

json_response([
    'title'     => $content['title'] ?: $book['title'],
    'chapters'  => array_map(fn($c, $i) => [
        'index' => $i,
        'title' => $c['title'],
        'html'  => $c['html'],
    ], $content['chapters'], array_keys($content['chapters'])),
]);
