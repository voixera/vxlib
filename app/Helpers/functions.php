<?php

declare(strict_types=1);

/** Small global helpers shared across the app. */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL relative to APP_URL. */
/** Base URL derived from the incoming request (fallback when APP_URL is unset/misconfigured). */
function request_base_url(): string
{
    static $derived = null;
    if ($derived !== null) return $derived;

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '' || PHP_SAPI === 'cli' || !preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
        return $derived = '';
    }
    // Behind proxies (Vercel/Cloudflare) trust the forwarded protocol.
    $proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if (!in_array($proto, ['https', 'http'], true)) {
        $proto = ($_SERVER['HTTPS'] ?? '') !== '' ? 'https' : 'http';
    }
    return $derived = $proto . '://' . $host;
}

function url(string $path = '/'): string
{
    $base = rtrim((string)Config::get('APP_URL', ''), '/');
    // Self-heal: never emit absolute localhost URLs to real visitors.
    if ($base === '' || str_contains($base, 'localhost') || str_contains($base, '127.0.0.1')) {
        $base = request_base_url() ?: $base;
    }
    return $base . ($path === '/' ? '/' : $path);
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function redirect(string $path, int $code = 302): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, $code);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Render a view from resources/views with optional scoped variables. */
function view(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require dirname(__DIR__, 2) . '/resources/views/' . $template . '.php';
}

/** Load a reusable component from resources/views/components. */
function component(string $name): void
{
    static $loaded = [];
    $file = VOIXLIB_ROOT . '/resources/views/components/' . preg_replace('/[^a-z0-9_\-]/i', '', $name) . '.php';
    if (!isset($loaded[$file])) {
        $loaded[$file] = true;
        require_once $file;
    }
}

/** Render a full page inside the main layout. */
function page(string $template, array $page = [], array $vars = []): void
{
    $vars['__template'] = $template;
    $vars['__page'] = $page;
    view('layout/page', $vars);
}
