<?php

declare(strict_types=1);

/** VoiXLib bootstrap: autoload app classes, load config, boot session lazily. */

define('VOIXLIB_ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    foreach (['Services', 'Repositories', 'Security', 'Helpers', 'Controllers', 'Middleware'] as $dir) {
        $file = VOIXLIB_ROOT . '/app/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require_once VOIXLIB_ROOT . '/config/config.php';
require_once VOIXLIB_ROOT . '/app/Helpers/functions.php';

Config::load();

// Development error surface; production stays quiet and logs.
if (Config::get('APP_ENV') === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

date_default_timezone_set('UTC');

// Never let caches leak privileged responses.
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
