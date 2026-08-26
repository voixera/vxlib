<?php

declare(strict_types=1);

/** Secure session bootstrap + CSRF protection. */

final class Security
{
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_name((string)Config::get('SESSION_NAME', 'voixlib_session'));
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 14,
            'path'     => '/',
            'domain'   => '',
            'secure'   => str_starts_with((string)Config::get('APP_URL', ''), 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Periodic session rotation.
        $now = time();
        if (!isset($_SESSION['_rotated'])) {
            $_SESSION['_rotated'] = $now;
        } elseif ($now - (int)$_SESSION['_rotated'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_rotated'] = $now;
        }
    }

    public static function csrfToken(): string
    {
        self::bootSession();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    /** Validate CSRF from POST body or X-CSRF-Token header. Exits 419 on failure. */
    public static function verifyCsrf(): void
    {
        self::bootSession();
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || $sent === '' || empty($_SESSION['_csrf'])
            || !hash_equals($_SESSION['_csrf'], $sent)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'csrf_failed', 'message' => 'Your session expired. Refresh the page and try again.']);
            exit;
        }
    }

    public static function destroySession(): void
    {
        self::bootSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}
