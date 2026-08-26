<?php

declare(strict_types=1);

/** ManhwaService — facade metadata manhwa Korea (AniList, MANGA + country KR). */

final class ManhwaService
{
    public static function browse(array $params): ?array
    {
        return AniListService::browse(['type' => 'manhwa'] + $params);
    }

    public static function detail(int $id): ?array
    {
        return AniListService::detail($id);
    }
}
