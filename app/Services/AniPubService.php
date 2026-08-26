<?php

declare(strict_types=1);

/** Optional AniPub metadata. AniPub IDs are not AniList or MAL IDs. */
final class AniPubService
{
    /** @return array<string,mixed>|null */
    public static function info(int $id): ?array
    {
        if ($id < 1) return null;

        $base = rtrim((string)Config::get('ANIPUB_API_URL', ''), '/');
        if ($base === '') return null;

        return Cache::remember('anipub:info:' . $id, 21600, fn() => Http::getJson($base . '/api/info/' . $id, 8));
    }
}
