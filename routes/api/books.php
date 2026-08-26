<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** GET /api/books.php — catalog query endpoint used by explore/search JS. */

Security::bootSession();

if (!RateLimiter::allow('api_books', 120, 60)) {
    json_response(['error' => 'rate_limited'], 429);
}

$params = ExploreController::paramsFromRequest();
$result = (new BookRepository())->search($params);

if ($result['error'] === 'supabase_not_configured') {
    json_response(['error' => 'not_configured', 'books' => []], 503);
}
if ($result['error']) {
    json_response(['error' => 'catalog_unavailable', 'books' => [], 'detail' => $result['error']], 502);
}

json_response([
    'books' => array_map([BookRepository::class, 'hydratePublic'], $result['books']),
    'total' => $result['total'],
    'page'  => $params['page'],
]);
