<?php

declare(strict_types=1);

/**
 * MediaNormalizer — mengubah Media GraphQL AniList menjadi bentuk internal.
 * Field yang tidak disediakan provider dibiarkan null: TIDAK PERNAH dikarang.
 */

final class MediaNormalizer
{
    private const STATUS_ID = [
        'RELEASING'        => 'Sedang Berlangsung',
        'FINISHED'         => 'Selesai',
        'NOT_YET_RELEASED' => 'Belum Rilis',
        'CANCELLED'        => 'Dibatalkan',
        'HIATUS'           => 'Hiatus',
    ];

    private const FORMAT_ID = [
        'MANGA' => 'Manga', 'ONE_SHOT' => 'One-shot', 'LIGHT_NOVEL' => 'Light Novel',
        'NOVEL' => 'Novel', 'DOUJINSHI' => 'Doujinshi',
    ];

    public static function item(array $m, bool $full = false): array
    {
        $type = self::mediaType($m);
        [$author, $artist] = self::staff($m);

        $desc = trim(strip_tags((string)($m['description'] ?? '')));
        $desc = str_replace(['&ZeroWidthSpace;', '(Source: ', "\n"], ['', '', "\n"], $desc);
        // Potong sinopsis panjang untuk kartu; halaman detail memakai versi penuh.
        if (!$full && mb_strlen($desc) > 180) $desc = mb_substr($desc, 0, 178) . '…';

        $title = (string)($m['title']['english'] ?? '') !== '' ? $m['title']['english'] : ($m['title']['romaji'] ?? '');
        $alt = (string)($m['title']['native'] ?? '') !== '' ? $m['title']['native'] : (($m['synonyms'][0] ?? '') ?: null);

        $item = [
            'id'            => (int)$m['id'],
            'external_id'   => 'anilist:' . (int)$m['id'],
            'media_type'    => $type,
            'type_label'    => $type === 'manhwa' ? 'Manhwa' : 'Manga',
            'title'         => $title,
            'title_romaji'  => $m['title']['romaji'] ?? null,
            'alt_title'     => $alt,
            'cover_url'     => $m['coverImage']['large'] ?? ($m['coverImage']['extraLarge'] ?? null),
            'banner_url'    => $m['bannerImage'] ?? null,
            'author'        => $author ?? 'Tidak tersedia',
            'artist'        => $artist,
            'genres'        => array_values((array)($m['genres'] ?? [])),
            'status_label'  => self::STATUS_ID[$m['status'] ?? ''] ?? null,
            'format_label'  => self::FORMAT_ID[$m['format'] ?? ''] ?? null,
            'year'          => $m['seasonYear'] ?? ($m['startDate']['year'] ?? null),
            'chapters'      => $m['chapters'] ?? null,
            'volumes'       => $m['volumes'] ?? null,
            'score'         => $m['averageScore'] ?? null,       // 0..100
            'popularity'    => $m['popularity'] ?? null,
            'favourites'    => $m['favourites'] ?? null,
            'mal_id'        => $m['idMal'] ?? null,
            'source_url'    => $m['siteUrl'] ?? ('https://anilist.co/manga/' . (int)$m['id']),
            'description'   => $desc !== '' ? $desc : null,
            'readable'      => false, // katalog = discovery; baca lewat sumber resmi
            'url_detail'    => '/detail/' . $type . '/' . (int)$m['id'],
        ];
        if (!$full) {
            unset($item['favourites'], $item['mal_id'], $item['title_romaji'], $item['format_label']);
        }
        return $item;
    }

    /** Genre dasar untuk filter jelajah; digabung dinamis dengan genre dari API di controller. */
    public static function genres(): array
    {
        return ['Action','Adventure','Comedy','Drama','Fantasy','Horror','Mystery','Romance','Sci-Fi','Slice of Life','Sports','Thriller'];
    }

    private static function mediaType(array $m): string
    {
        return strtoupper((string)($m['countryOfOrigin'] ?? '')) === 'KR' ? 'manhwa' : 'manga';
    }

    /** @return array{0:?string,1:?string} */
    private static function staff(array $m): array
    {
        $author = null;
        $artist = null;
        foreach ((array)($m['staff']['edges'] ?? []) as $edge) {
            $role = strtolower((string)($edge['role'] ?? ''));
            $name = $edge['node']['name']['full'] ?? null;
            if ($name === null) continue;
            if ($author === null && (str_contains($role, 'story') || str_contains($role, 'original creator'))) $author = $name;
            if ($artist === null && (str_contains($role, 'art') || str_contains($role, 'character design'))) $artist = $name;
        }
        return [$author, $artist];
    }
}
