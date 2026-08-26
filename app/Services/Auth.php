<?php

declare(strict_types=1);

/** Session-backed auth + Discord identity helpers. */

final class Auth
{
    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        return Security::readAuthCookie();
    }

    /** Full user row from Supabase (memoized per request). */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) return self::$cachedUser ?: null;
        $id = self::id();
        if ($id === null) return null;
        $user = (new UserRepository())->find($id);
        self::$cachedUser = $user ?: [];
        return $user;
    }

    public static function login(array $userRow): void
    {
        Security::bootSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        Security::issueAuthCookie((int)$userRow['id']);
        self::$cachedUser = $userRow;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        Security::destroySession();
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        if (!$u || empty($u['discord_id'])) return false;
        return in_array((string)$u['discord_id'], Config::adminDiscordIds(), true);
    }

    /** Guard: redirect anonymous visitors to login with a return path. */
    public static function requireUser(): array
    {
        $u = self::user();
        if (!$u) {
            $next = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/login.php?required=1&next=' . rawurlencode(is_string($next) ? $next : '/'));
        }
        return $u;
    }
}
