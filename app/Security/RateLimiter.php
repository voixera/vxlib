<?php

declare(strict_types=1);

/** Simple file-based fixed-window rate limiter (per IP + action). */

final class RateLimiter
{
    private static ?string $dir = null;

    private static function dir(): string
    {
        if (self::$dir !== null) return self::$dir;
        $preferred = dirname(__DIR__, 2) . '/storage/ratelimit';
        if (is_dir($preferred) && is_writable($preferred)) {
            return self::$dir = $preferred;
        }
        $tmp = sys_get_temp_dir() . '/voixlib-ratelimit';
        if (!is_dir($tmp)) @mkdir($tmp, 0775, true);
        return self::$dir = $tmp;
    }

    public static function allow(string $action, int $limit, int $windowSeconds): bool
    {
        $dir = self::dir();
        $key = md5($action . '|' . client_ip());
        $file = $dir . '/' . $key . '.json';
        $now = time();

        $state = ['count' => 0, 'reset' => $now + $windowSeconds];
        if (is_file($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded) && ($decoded['reset'] ?? 0) > $now) {
                $state = $decoded;
            }
        }

        if ($state['count'] >= $limit) return false;

        $state['count']++;
        @file_put_contents($file, json_encode($state), LOCK_EX);
        return true;
    }
}
