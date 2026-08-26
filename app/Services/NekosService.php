<?php

declare(strict_types=1);

/** Safe decorative anime art. Never used as media content. */
final class NekosService
{
    private const ENDPOINT = 'https://api.nekosapi.com/v4/images/random?rating=safe&without_tags=exposed_girl_breasts,breasts,nude,nsfw&limit=1';
    private const BLOCKED_TAGS = ['breast', 'nude', 'nsfw', 'sex', 'explicit', 'exposed'];

    /** @return array{url:string,artist:?string,source:?string}|null */
    public static function safeImage(): ?array
    {
        return Cache::remember('nekos:safe-image', 3600, function (): ?array {
            $items = Http::getJson(self::ENDPOINT, 8);
            $image = $items[0] ?? null;
            if (!is_array($image) || ($image['rating'] ?? '') !== 'safe') return null;

            foreach ((array)($image['tags'] ?? []) as $tag) {
                foreach (self::BLOCKED_TAGS as $blocked) {
                    if (str_contains(strtolower((string)$tag), $blocked)) return null;
                }
            }

            $url = (string)($image['url'] ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_HOST) !== 'cdn.nekosapi.com') return null;

            return [
                'url' => $url,
                'artist' => isset($image['artist_name']) ? (string)$image['artist_name'] : null,
                'source' => isset($image['source_url']) ? (string)$image['source_url'] : null,
            ];
        });
    }
}
