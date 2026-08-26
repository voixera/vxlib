<?php

declare(strict_types=1);

/** Minimal file cache with TTL. Used to avoid hammering external APIs. */

final class Cache
{
    private static string $dir = __DIR__ . '/../../storage/cache';

    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) return null;
        $raw = @file_get_contents($file);
        if ($raw === false) return null;
        $entry = json_decode($raw, true);
        if (!is_array($entry) || !isset($entry['expires'], $entry['value'])) {
            @unlink($file);
            return null;
        }
        if ($entry['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $entry['value'];
    }

    public static function set(string $key, mixed $value, int $ttlSeconds): void
    {
        if (!is_dir(self::$dir)) @mkdir(self::$dir, 0775, true);
        @file_put_contents(
            self::path($key),
            json_encode(['expires' => time() + $ttlSeconds, 'value' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    /** Get-or-compute helper. */
    public static function remember(string $key, int $ttlSeconds, callable $producer): mixed
    {
        $hit = self::get($key);
        if ($hit !== null) return $hit;
        $value = $producer();
        self::set($key, $value, $ttlSeconds);
        return $value;
    }

    private static function path(string $key): string
    {
        return self::$dir . '/' . md5($key) . '.cache.json';
    }
}
