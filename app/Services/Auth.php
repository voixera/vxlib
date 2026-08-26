<?php

declare(strict_types=1);

/** Session-backed auth + Discord identity helpers. */

final class Auth
{
    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        Security::bootSession();
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        Security::bootSession();
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
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
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$userRow['id'];
        $_SESSION['_rotated'] = time();
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
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/login.php?required=1');
        }
        return $u;
    }
}
