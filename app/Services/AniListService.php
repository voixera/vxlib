<?php

declare(strict_types=1);

/**
 * AniListService — satu-satunya sumber data katalog (graphql.anilist.co).
 * Metadata nyata saja: tidak ada judul/sinopsis/rating buatan.
 * Semua respons di-cache supaya hemat kuota rate limit provider.
 */

final class AniListService
{
    public const ENDPOINT = 'https://graphql.anilist.co';

    /** @var array<string,int>|null */
    private static ?array $rateRemaining = null;

    /** Raw GraphQL request dengan cache. Hasil gagal TIDAK di-cache (coba lagi nanti). */
    public static function query(string $query, array $variables, int $ttlSeconds): ?array
    {
        ksort($variables);
        $key = 'anilist:' . md5($query . '|' . json_encode($variables));
        $hit = Cache::get($key);
        if ($hit !== null) return $hit;
        $data = self::fetch($query, $variables);
        if ($data !== null) Cache::set($key, $data, $ttlSeconds);
        return $data;
    }

    private static function fetch(string $query, array $variables): ?array
    {
        $payload = json_encode(['query' => $query, 'variables' => $variables]);
        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($body) || $status >= 500) return null;

        $data = json_decode($body, true);
        if (!is_array($data)) return null;
        if (isset($data['errors']) && empty($data['data'])) return null;
        return $data['data'] ?? null;
    }

    private const MEDIA_FIELDS = '
      id idMal
      type
      title { romaji english native }
      synonyms
      coverImage { extraLarge large color }
      bannerImage
      description
      genres
      status format countryOfOrigin
      seasonYear startDate { year }
      chapters volumes episodes
      averageScore meanScore popularity favourites
      siteUrl
      staff(perPage: 6) { edges { role node { name { full } } } }
    ';

    /** Grid hasil pencarian / jelajah. */
    public static function browse(array $o): ?array
    {
        $vars = [
            'page' => max(1, (int)($o['page'] ?? 1)),
            'perPage' => min(24, max(6, (int)($o['per_page'] ?? 24))),
            'sort' => self::sorts((string)($o['sort'] ?? 'popular')),
        ];
        // Susun argumen secara dinamis: variabel null jangan dikirim (AniList memperlakukan
        // null sebagai filter aktif untuk countryOfOrigin).
        $decl = '$page:Int,$perPage:Int,$sort:[MediaSort]';
        $mediaArgs = 'sort:$sort,isAdult:false';

        if (!empty($o['type'])) {
            [$type, $country] = self::typeFilter((string)$o['type']);
            if ($type !== null) {
                $decl .= ',$_t:MediaType';
                $vars['_t'] = $type;
                $mediaArgs .= ',type:$_t';
            }
            if ($country !== null) {
                $decl .= ',$_c:CountryCode';
                $vars['_c'] = $country;
                $mediaArgs .= ',countryOfOrigin:$_c';
            }
        }
        if (($q2 = trim((string)($o['q'] ?? ''))) !== '') {
            $decl .= ',$_s:String';
            $vars['_s'] = mb_substr($q2, 0, 80);
            $mediaArgs .= ',search:$_s';
        }
        if (!empty($o['genre']) && $o['genre'] !== 'all') {
            $decl .= ',$_g:String';
            $vars['_g'] = (string)$o['genre'];
            $mediaArgs .= ',genre:$_g';
        }
        if (!empty($o['status']) && $o['status'] !== 'all') {
            $map = ['releasing' => 'RELEASING', 'finished' => 'FINISHED', 'upcoming' => 'NOT_YET_RELEASED', 'hiatus' => 'HIATUS'];
            if (isset($map[(string)$o['status']])) {
                $decl .= ',$_st:MediaStatus';
                $vars['_st'] = $map[$o['status']];
                $mediaArgs .= ',status:$_st';
            }
        }
        $yf = (int)($o['year_from'] ?? 0);
        $yt = (int)($o['year_to'] ?? 0);
        if ($yf > 1900) {
            $decl .= ',$_yf:FuzzyDateInt';
            $vars['_yf'] = $yf * 10000;
            $mediaArgs .= ',startDate_greater:$_yf';
        }
        if ($yt > 1900) {
            $decl .= ',$_yt:FuzzyDateInt';
            $vars['_yt'] = ($yt + 1) * 10000;
            $mediaArgs .= ',startDate_lesser:$_yt';
        }

        $q = 'query(' . $decl . '){Page(page:$page,perPage:$perPage){pageInfo{total currentPage hasNextPage}'
           . 'media(' . $mediaArgs . '){' . self::MEDIA_FIELDS . '} }}';

        $data = self::query($q, $vars, 1800);
        $page = $data['Page'] ?? null;
        if (!$page) return null;
        return [
            'items' => array_map([MediaNormalizer::class, 'item'], (array)($page['media'] ?? [])),
            'total' => $page['pageInfo']['total'] ?? null,
            'has_next' => (bool)($page['pageInfo']['hasNextPage'] ?? false),
            'page' => $vars['page'],
        ];
    }

    /** Satu judul berdasarkan id numerik AniList. */
    public static function detail(int $id): ?array
    {
        $q = 'query($id:Int){Media(id:$id){' . self::MEDIA_FIELDS . ' }}';
        $data = self::query($q, ['id' => $id], 86400);
        $m = $data['Media'] ?? null;
        return $m ? MediaNormalizer::item($m, full: true) : null;
    }

    /** Beberapa shelf untuk beranda; semuanya dari data trending/populer provider. */
    public static function shelves(): array
    {
        return [
            'trending' => self::browse(['sort' => 'trending', 'per_page' => 12, 'page' => 1]),
            'anime'    => self::browse(['sort' => 'popular', 'type' => 'anime', 'per_page' => 12, 'page' => 1]),
            'manga'    => self::browse(['sort' => 'popular', 'type' => 'manga', 'per_page' => 12, 'page' => 1]),
            'manhwa'   => self::browse(['sort' => 'popular', 'type' => 'manhwa', 'per_page' => 12, 'page' => 1]),
            'picks'    => self::browse(['sort' => 'score', 'per_page' => 10, 'page' => 1]),
        ];
    }

    private static function sorts(string $key): array
    {
        return match ($key) {
            'newest'      => ['START_DATE_DESC'],
            'oldest'      => ['START_DATE_ASC'],
            'title_asc'   => ['TITLE_ROMAJI_ASC'],
            'title_desc'  => ['TITLE_ROMAJI_DESC'],
            'score'       => ['SCORE_DESC'],
            'trending'    => ['TRENDING_DESC'],
            default       => ['POPULARITY_DESC'],
        };
    }

    /** @return array{0:?string,1:?string} */
    private static function typeFilter(string $slug): array
    {
        return match ($slug) {
            'anime'  => ['ANIME', null],
            'manga'  => ['MANGA', 'JP'],
            'manhwa' => ['MANGA', 'KR'],
            default  => [null, null],
        };
    }
}
