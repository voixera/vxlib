<?php

/**
 * Router for PHP's built-in dev server so 404s render VoiXLib's error page:
 *   php -S localhost:8000 -t public tools/router.php
 * Production (Apache/nginx) does not need this file.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public' . $path;

if ($path !== '/' && (is_file($file) || preg_match('/\.(css|js|svg|png|jpg|ico|map|woff2?)$/', $path))) {
    if (is_file($file)) return false; // serve static/existing files directly
}

if (!preg_match('/\.php$/', $path)) {
    // Pretty paths fall back to the front controller.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . '/../public/index.php';
    return true;
}

$target = __DIR__ . '/../public' . $path;
if (is_file($target)) {
    chdir(dirname($target));
    require $target;
    return true;
}

http_response_code(404);
require __DIR__ . '/../public/404.php';
return true;
