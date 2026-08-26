<?php

/**
 * VoiXLib serverless front controller (Vercel).
 * Custom runtimes only build functions from /api, so this single lambda
 * fans requests out to the page controllers under routes/.
 *
 * Also usable locally as a router:
 *   php -S localhost:8000 api/index.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// ── Built-in-server convenience: let it serve real files itself ──────────
// (On Vercel this branch never triggers: rewrites route everything here.)
if (PHP_SAPI === 'cli-server') {
    $file = $root . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

// ── Static assets should never reach PHP ─────────────────────────────────
if (preg_match('#\.(css|js|svg|png|jpg|jpeg|webp|ico|woff2?|map|txt)$#i', $path)
    || str_starts_with($path, '/assets/')) {
    // Local dev parity: Vercel rewrites serve these before PHP; the built-in
    // server needs us to stream them from public/assets/.
    $asset = $root . '/public' . $path;
    if (PHP_SAPI === 'cli-server' && is_file($asset)) {
        $types = [
            'css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'map' => 'application/json',
        ];
        $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($asset));
        readfile($asset);
        return true;
    }
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    return true;
}

// ── Resolve the target page under routes/ ────────────────────────────────
$target = null;

if ($path === '/' || $path === '/index.php') {
    $target = 'routes/index.php';
} else {
    // /book.php        → routes/book.php
    // /api/books.php   → routes/api/books.php
    // /auth/callback.php → routes/auth/callback.php
    if (preg_match('#^/([a-z0-9_\-]+)\.php$#i', $path, $m)) {
        $candidate = 'routes/' . strtolower($m[1]) . '.php';
        if (is_file($root . '/' . $candidate)) {
            $target = $candidate;
        }
    } elseif (preg_match('#^/(api|auth)/([a-z0-9_\-]+)\.php$#i', $path, $m)) {
        $candidate = 'routes/' . strtolower($m[1]) . '/' . strtolower($m[2]) . '.php';
        if (is_file($root . '/' . $candidate)) {
            $target = $candidate;
        }
    }
}

require $root . '/app/bootstrap.php';

if ($target !== null) {
    chdir($root . '/' . dirname($target));
    require $root . '/' . $target;
    return true;
}

// ── Pretty 404 ────────────────────────────────────────────────────────────
http_response_code(404);
page('errors/404', ['title' => 'This shelf doesn’t exist — VoiXLib', 'activeNav' => ''], ['message' => null]);
return true;
