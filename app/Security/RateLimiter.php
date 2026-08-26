<?php

declare(strict_types=1);

/** Simple file-based fixed-window rate limiter (per IP + action). */

final class RateLimiter
{
    private static string $dir = __DIR__ . '/../../storage/ratelimit';

    public static function allow(string $action, int $limit, int $windowSeconds): bool
    {
        if (!is_dir(self::$dir)) @mkdir(self::$dir, 0775, true);
        $key = md5($action . '|' . client_ip());
        $file = self::$dir . '/' . $key . '.json';
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
