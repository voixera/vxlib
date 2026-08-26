<?php

declare(strict_types=1);

/**
 * Security primitives.
 *
 * Designed to work on both classic hosts (PHP sessions available) and
 * serverless platforms like Vercel (no shared filesystem):
 *  - Auth identity: HMAC-signed cookie (stateless, expiring).
 *  - OAuth state / intended URL: short-lived signed cookies.
 *  - CSRF: double-submit cookie pattern.
 */

final class Security
{
    private const AUTH_COOKIE   = 'voixlib_auth';
    private const STATE_COOKIE  = 'voixlib_oauth';
    public  const CSRF_COOKIE   = 'voixlib_csrf';

    private const AUTH_TTL = 60 * 60 * 24 * 14; // 14 days
    private const STATE_TTL = 600;              // 10 minutes

    /** Boot a best-effort PHP session (used only for cosmetic state; auth does not depend on it). */
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        if (headers_sent()) return;

        session_name((string)Config::get('SESSION_NAME', 'voixlib_session'));
        try {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            @session_start();
        } catch (\Throwable) {
            // Serverless/read-only environments: everything security-critical uses cookies instead.
        }
    }

    private static function isHttps(): bool
    {
        if (str_starts_with((string)Config::get('APP_URL', ''), 'https://')) return true;
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || ($_SERVER['HTTPS'] ?? '') !== '';
    }

    /** Signing key: APP_SECRET, falling back to the Discord client secret. */
    private static function secret(): string
    {
        return (string)(Config::get('APP_SECRET') ?? Config::get('DISCORD_CLIENT_SECRET') ?? 'voixlib-dev-secret');
    }

    /** Signed payload cookie: base64(json).hmac */
    public static function setSignedCookie(string $name, array $payload, int $ttl): void
    {
        $payload['exp'] = time() + $ttl;
        $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $mac  = hash_hmac('sha256', $body, self::secret());
        setcookie($name, $body . '.' . $mac, [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$name] = $body . '.' . $mac;
    }

    /** Read + verify a signed cookie. Returns payload or null. */
    public static function getSignedCookie(string $name): ?array
    {
        $raw = $_COOKIE[$name] ?? '';
        if (!is_string($raw) || substr_count($raw, '.') !== 1) return null;
        [$body, $mac] = explode('.', $raw, 2);
        if ($body === '' || $mac === '') return null;
        if (!hash_equals(hash_hmac('sha256', $body, self::secret()), $mac)) return null;

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')) ?: '', true);
        if (!is_array($payload) || !isset($payload['exp']) || (int)$payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    public static function clearCookie(string $name): void
    {
        setcookie($name, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => self::isHttps(), 'httponly' => true, 'samesite' => 'Lax']);
        unset($_COOKIE[$name]);
    }

    // ── Auth identity ────────────────────────────────────────────

    public static function issueAuthCookie(int $userId): void
    {
        self::setSignedCookie(self::AUTH_COOKIE, ['uid' => $userId], self::AUTH_TTL);
    }

    public static function readAuthCookie(): ?int
    {
        $payload = self::getSignedCookie(self::AUTH_COOKIE);
        if (!$payload || !isset($payload['uid'])) return null;
        $uid = filter_var($payload['uid'], FILTER_VALIDATE_INT);
        return $uid !== false && $uid > 0 ? $uid : null;
    }

    public static function clearAuthCookie(): void
    {
        self::clearCookie(self::AUTH_COOKIE);
    }

    // ── OAuth handshake state ────────────────────────────────────

    public static function beginOAuth(string $intendedPath): string
    {
        $state = bin2hex(random_bytes(24));
        self::setSignedCookie(self::STATE_COOKIE, [
            's' => $state,
            'next' => mb_substr($intendedPath, 0, 200),
        ], self::STATE_TTL);
        return $state;
    }

    /** Validate + consume the OAuth state cookie. Returns ['next'=>string] or null. */
    public static function consumeOAuthState(?string $sentState): ?array
    {
        $payload = self::getSignedCookie(self::STATE_COOKIE);
        self::clearCookie(self::STATE_COOKIE);
        if (!$payload || empty($payload['s']) || !is_string($sentState)) return null;
        if (!hash_equals((string)$payload['s'], $sentState)) return null;
        return ['next' => is_string($payload['next'] ?? null) ? $payload['next'] : '/'];
    }

    // ── CSRF (double-submit cookie) ──────────────────────────────

    public static function csrfToken(): string
    {
        $token = $_COOKIE[self::CSRF_COOKIE] ?? '';
        if (!is_string($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
            $token = bin2hex(random_bytes(32));
            setcookie(self::CSRF_COOKIE, $token, [
                'expires'  => 0,
                'path'     => '/',
                'secure'   => self::isHttps(),
                'httponly' => true, // pages embed the token server-side; JS reads it from window.VOIXLIB
                'samesite' => 'Lax',
            ]);
            $_COOKIE[self::CSRF_COOKIE] = $token;
        }
        return $token;
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    /**
     * Double-submit check: header/POST token must match the cookie token.
     * Falls back to accepting a valid signed session token on classic hosts.
     */
    public static function verifyCsrf(): void
    {
        self::bootSession();
        $cookieToken = $_COOKIE[self::CSRF_COOKIE] ?? '';
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (is_string($sent) && $sent !== '' && is_string($cookieToken)
            && strlen($sent) === 64 && hash_equals($cookieToken, $sent)) {
            return;
        }

        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'csrf_failed', 'message' => 'Your session expired. Refresh the page and try again.']);
        exit;
    }

    public static function destroySession(): void
    {
        self::bootSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
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
        self::clearAuthCookie();
        self::clearCookie(self::STATE_COOKIE);
    }
}
