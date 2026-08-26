<?php

declare(strict_types=1);

/** AnimeService — facade metadata anime (AniList). Tukar isi class ini jika provider diganti. */

final class AnimeService
{
    public static function browse(array $params): ?array
    {
        return AniListService::browse(['type' => 'anime'] + $params);
    }

    public static function detail(int $id): ?array
    {
        return AniListService::detail($id);
    }
}
