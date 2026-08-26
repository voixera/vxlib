<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Manga page renderer: GET /page.php?b=<external_id>&p=<n>
 * Deterministic original SVG comic page for VoiXLib-native books.
 */

Security::bootSession();

if (!RateLimiter::allow('manga_page', 240, 60)) {
    http_response_code(429);
    header('Content-Type: text/plain');
    exit('rate_limited');
}

$b = trim((string)($_GET['b'] ?? ''));
$p = (int)($_GET['p'] ?? 0);
if (!preg_match('/^[0-9A-Za-z:_\-]{1,64}$/', $b) || $p < 1 || $p > 64) {
    http_response_code(422);
    header('Content-Type: text/plain');
    exit('invalid_request');
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800, immutable');
header('X-Content-Type-Options: nosniff');

try {
    echo MangaService::pageSvg($b, $p);
} catch (\Throwable) {
    http_response_code(404);
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1200"><rect width="800" height="1200" fill="#EFEAE0"/></svg>';
}
