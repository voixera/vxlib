<?php

declare(strict_types=1);

/** Minimal file cache with TTL. Used to avoid hammering external APIs. */

final class Cache
{
    private static ?string $dir = null;

    /** Writable cache dir; falls back to system temp (serverless filesystems). */
    private static function dir(): string
    {
        if (self::$dir !== null) return self::$dir;
        $preferred = dirname(__DIR__, 2) . '/storage/cache';
        if (is_dir($preferred) && is_writable($preferred)) {
            return self::$dir = $preferred;
        }
        $tmp = sys_get_temp_dir() . '/voixlib-cache';
        if (!is_dir($tmp)) @mkdir($tmp, 0775, true);
        return self::$dir = $tmp;
    }

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
        $dir = self::dir();
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
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
        return self::dir() . '/' . md5($key) . '.cache.json';
    }
}
