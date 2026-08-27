<?php

declare(strict_types=1);

/**
 * WeebCentralProvider — provider pembaca manga/manhwa/manhua dari WeebCentral.
 *
 * WeebCentral adalah agregator komunitas dengan API publik (tanpa API key, tanpa auth).
 * Endpoint yang dipakai (HTML ter-render, di-parse via DOMDocument):
 *   - Search      : POST /search/simple?location=main
 *   - Detail      : GET  /series/{id}
 *   - Chapter list: GET  /series/{id}/full-chapter-list
 *   - Chapter page: GET  /chapters/{id}/images?is_prev=False
 *
 * Gambar chapter di-host di CDN pihak ketiga (planeptune.us dkk) dan bisa di-embed
 * langsung. Tidak ada credential ilegal / bypass authentication / paywall di sini.
 */

final class WeebCentralProvider implements MangaProvider, ChapterProvider, ReaderProvider
{
    private const DEFAULT_BASE = 'https://weebcentral.com';

    private static function base(): string
    {
        return rtrim((string)Config::get('WEEBCENTRAL_API_URL', self::DEFAULT_BASE), '/');
    }

    private static function doc(string $html): ?DOMDocument
    {
        if ($html === '') return null;
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $doc;
    }

    /** @return array<string,?string> */
    private static function parseOg(string $html): array
    {
        $out = [];
        foreach (['title', 'image', 'description'] as $k) {
            $pat1 = '/<meta[^>]+property=["\']og:' . preg_quote($k, '/') . '["\'][^>]+content=["\']([^"\']*)["\']/i';
            $pat2 = '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']og:' . preg_quote($k, '/') . '["\']/i';
            if (preg_match($pat1, $html, $m) || preg_match($pat2, $html, $m)) {
                $out[$k] = html_entity_decode((string)$m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return $out;
    }

    public static function getName(): string
    {
        return 'WeebCentral';
    }

    public static function getProviderName(): string
    {
        return self::getName();
    }

    /** {@inheritdoc} */
    public static function search(string $query, int $page = 1): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['items' => [], 'has_next' => false, 'query' => $q];
        }

        $key = 'weebcentral:search:' . md5(strtolower($q) . ':' . $page);
        $items = Cache::remember($key, 1800, function () use ($q) {
            $html = Http::postText(self::base() . '/search/simple?location=main', 'text=' . urlencode($q), 20, [
                'Content-Type: application/x-www-form-urlencoded',
                'HX-Request: true',
                'HX-Current-Url: ' . self::base() . '/',
            ]);
            if ($html === null) return [];

            $doc = self::doc($html);
            if (!$doc) return [];

            $x = new DOMXPath($doc);
            $seen = [];
            $out = [];
            foreach ($x->query("//a[contains(@href,'/series/')]") as $a) {
                $href = (string)$a->getAttribute('href');
                if (!preg_match('#/series/([0-9A-Z]+)#', $href, $mm)) continue;
                $id = $mm[1];
                if (isset($seen[$id])) continue;
                $seen[$id] = true;

                $img = $x->query('.//img', $a)->item(0);
                $cover = $img ? (string)$img->getAttribute('src') : null;
                $title = trim(preg_replace('/\s+/', ' ', (string)$a->textContent));

                $out[] = [
                    'id'         => $id,
                    'title'      => $title,
                    'cover'      => $cover,
                    'type'       => null,
                    'url_detail' => '/manga/detail/' . $id,
                ];
            }
            return $out;
        });

        return ['items' => $items, 'has_next' => false, 'query' => $q];
    }

    /** {@inheritdoc} */
    public static function detail(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') return null;

        $key = 'weebcentral:detail:' . $id;
        return Cache::remember($key, 21600, function () use ($id) {
            $html = Http::getText(self::base() . '/series/' . $id, 20, ['HX-Request: true']);
            if ($html === null) return null;

            $og = self::parseOg($html);
            $authors = [];
            $genres = [];
            $status = null;
            $type = null;
            $altNames = [];

            $doc = self::doc($html);
            if ($doc) {
                $x = new DOMXPath($doc);
                foreach ($x->query('//strong') as $s) {
                    $label = trim((string)$s->textContent);
                    $next = $s->nextElementSibling;
                    $val = $next ? trim(preg_replace('/\s+/', ' ', (string)$next->textContent)) : '';
                    if ($val === '') continue;
                    if (str_starts_with($label, 'Author')) {
                        $authors = array_values(array_filter(array_map('trim', preg_split('/,|\n/', $val))));
                    } elseif (str_starts_with($label, 'Status')) {
                        $status = $val;
                    } elseif (str_starts_with($label, 'Type')) {
                        $type = $val;
                    } elseif (str_starts_with($label, 'Genres')) {
                        $genres = array_values(array_filter(array_map('trim', preg_split('/,|\n/', $val))));
                    } elseif (str_starts_with($label, 'Alternative')) {
                        $altNames = array_values(array_filter(array_map('trim', preg_split('/,|\n/', $val))));
                    }
                }
            }

            if (empty($og['title']) && empty($og['image'])) return null;

            // WeebCentral menempelkan nama situs pada og:title/description.
            $title = $og['title'] ?? null;
            if ($title !== null) {
                $title = preg_replace('/\s*[|\-–]\s*Weeb\s*Central\s*$/i', '', $title);
            }
            $desc = $og['description'] ?? null;
            if ($desc !== null && preg_match('/^Read .*? online for free at Weeb Central$/i', $desc)) {
                $desc = null;
            }

            return [
                'id'          => $id,
                'title'       => $title,
                'cover'       => $og['image'] ?? null,
                'description' => $desc,
                'authors'     => $authors,
                'genres'      => $genres,
                'status'      => $status,
                'type'        => $type,
                'alt_names'   => $altNames,
                'source_url'  => self::base() . '/series/' . $id,
                'url_detail'  => '/manga/detail/' . $id,
            ];
        });
    }

    /** {@inheritdoc} */
    public static function chapters(string $id, int $page = 1): array
    {
        $id = trim($id);
        if ($id === '') return [];

        $key = 'weebcentral:chapters:' . $id;
        $list = Cache::remember($key, 10800, function () use ($id) {
            $html = Http::getText(self::base() . '/series/' . $id . '/full-chapter-list', 25, ['HX-Request: true']);
            if ($html === null) return [];

            $doc = self::doc($html);
            if (!$doc) return [];

            $x = new DOMXPath($doc);
            $out = [];
            foreach ($x->query("//a[starts-with(@href,'/chapters/')]") as $a) {
                $href = (string)$a->getAttribute('href');
                if (!preg_match('#/chapters/([0-9A-Z]+)#', $href, $mm)) continue;
                $cid = $mm[1];

                $txt = trim(preg_replace('/\s+/', ' ', (string)$a->textContent));
                $txt = preg_replace('/Last Read/i', '', $txt);
                $txt = preg_replace('/\d{4}-\d{2}-\d{2}T.*$/', '', $txt);
                $txt = trim($txt);

                $timeNode = $x->query('.//time', $a)->item(0);
                $date = $timeNode ? (string)$timeNode->getAttribute('datetime') : null;

                $num = null;
                if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $txt, $nm)) {
                    $num = $nm[1];
                }

                $out[] = [
                    'id'     => $cid,
                    'number' => $num,
                    'title'  => $txt === '' ? 'Chapter' : $txt,
                    'date'   => $date,
                    'url'    => '/manga/read/' . $id . '/' . $cid,
                ];
            }
            return $out;
        });

        // Urutkan ascending (chapter 1 di awal) bila semua punya nomor,
        // else balik urutan server (terbaru → terlama).
        if (count($list) > 1) {
            $numbers = array_filter(array_column($list, 'number'));
            if (count($numbers) === count($list)) {
                usort($list, fn($a, $b) => (float)$a['number'] <=> (float)$b['number']);
            } else {
                $list = array_reverse($list);
            }
        }
        return $list;
    }

    /** {@inheritdoc} */
    public static function getPages(string $chapterId): array
    {
        $chapterId = trim($chapterId);
        if ($chapterId === '') return [];

        $key = 'weebcentral:pages:' . $chapterId;
        return Cache::remember($key, 21600, function () use ($chapterId) {
            $url = self::base() . '/chapters/' . $chapterId . '/images?is_prev=False';
            $html = Http::getText($url, 25, [
                'HX-Request: true',
                'HX-Current-Url: ' . self::base() . '/chapters/' . $chapterId,
            ]);
            if ($html === null) return [];

            $doc = self::doc($html);
            if (!$doc) return [];

            $x = new DOMXPath($doc);
            $pages = [];
            foreach ($x->query('//img') as $img) {
                $src = (string)$img->getAttribute('src');
                if ($src === '') $src = (string)$img->getAttribute('data-src');
                if ($src === '') continue;
                if (!preg_match('/\.(png|jpe?g|webp|avif)$/i', $src)) continue;
                if (str_contains($src, '/static/images/')) continue;
                $pages[] = $src;
            }
            return $pages;
        });
    }

    /** {@inheritdoc} */
    public static function read(string $seriesId, string $chapterId): ?array
    {
        $seriesId = trim($seriesId);
        if ($seriesId === '') return null;

        $series = self::detail($seriesId);
        if ($series === null) return null;

        $chapters = self::chapters($seriesId);

        if (empty($chapters)) {
            return [
                'series'  => $series,
                'chapters' => [],
                'current' => null,
                'prev'    => null,
                'next'    => null,
                'provider' => self::getName(),
            ];
        }

        $index = 0;
        if ($chapterId !== '') {
            foreach ($chapters as $i => $c) {
                if ($c['id'] === $chapterId) {
                    $index = $i;
                    break;
                }
            }
        }

        $current = $chapters[$index];
        $pages = self::getPages($current['id']);

        $prev = $index > 0 ? $chapters[$index - 1]['id'] : null;
        $next = $index < count($chapters) - 1 ? $chapters[$index + 1]['id'] : null;

        return [
            'series'  => $series,
            'chapters' => $chapters,
            'current' => [
                'id'     => $current['id'],
                'title'  => $current['title'],
                'number' => $current['number'],
                'index'  => $index,
                'pages'  => $pages,
            ],
            'prev'    => $prev,
            'next'    => $next,
            'provider' => self::getName(),
        ];
    }

    /** Legacy ChapterProvider adapter: cari via judul lalu kembalikan daftar chapter. */
    public static function findChapters(array $queries, ?int $year = null, ?string $author = null): array
    {
        foreach ($queries as $q) {
            $res = self::search((string)$q);
            if (!empty($res['items'])) {
                $first = $res['items'][0];
                $chs = self::chapters($first['id']);
                return array_map(fn($c) => [
                    'id'           => $c['id'],
                    'number'       => $c['number'] ?? '?',
                    'title'        => $c['title'],
                    'language'     => 'EN',
                    'publish_date' => $c['date'] ? substr($c['date'], 0, 10) : null,
                    'group'        => 'WeebCentral',
                    'source'       => 'weebcentral',
                ], $chs);
            }
        }
        return [];
    }
}
