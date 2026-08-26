<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** GET /api/books.php — hasil katalog AniList (dipakai JS jelajah/pencarian). */

Security::bootSession();

if (!RateLimiter::allow('api_books', 120, 60)) {
    json_response(['error' => 'rate_limited'], 429);
}

$params = ExploreController::paramsFromRequest();
$result = ExploreController::browse($params);

if ($result['error']) {
    json_response(['error' => 'provider_unavailable', 'books' => []], 502);
}

json_response([
    'books'     => array_map(fn($b) => [
        'external_id'  => $b['external_id'],
        'url_detail'   => $b['url_detail'],
        'media_type'   => $b['media_type'],
        'type_label'   => $b['type_label'],
        'title'        => $b['title'],
        'author'       => $b['author'],
        'cover_url'    => $b['cover_url'],
        'year'         => $b['year'],
        'score'        => $b['score'],
        'status_label' => $b['status_label'],
        'genres'       => array_map(fn($g) => ['name' => $g], array_slice($b['genres'], 0, 1)),
        'excerpt'      => $b['description'],
        'readable'     => false,
    ], $result['items']),
    'total'     => $result['total'],
    'page'      => $result['page'],
]);
