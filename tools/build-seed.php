<?php
/**
 * VoiXLib seed builder.
 * Pulls REAL public-domain book metadata from Gutendex (Project Gutenberg)
 * and enriches sparse records via the Open Library search API.
 * Output: storage/seed/books.json  (used by tools/seed.php to populate Supabase)
 *
 * Usage: php tools/build-seed.php
 */

declare(strict_types=1);

const GUTENDEX = 'https://gutendex.com/books';
const OL_SEARCH = 'https://openlibrary.org/search.json';
const UA = 'VoiXLib/1.0 (digital library; seed builder)';

$categories = [
    'Classics'            => 'classics',
    'Mystery & Detective' => 'detective',
    'Science Fiction'     => 'science fiction',
    'Fantasy'             => 'fantasy',
    'Romance'             => 'romance',
    'Gothic & Horror'     => 'horror',
    'Adventure'           => 'adventure',
    'Philosophy'          => 'philosophy',
    'History'             => 'history',
    'Short Stories'       => 'short stories',
    'Poetry'              => 'poetry',
    'Nature & Science'    => 'natural science',
];

function http_json(string $url, int $timeout = 25): ?array {
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => UA,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code === 200 && is_string($body)) {
            $json = json_decode($body, true);
            if (is_array($json)) return $json;
        }
        usleep(1500000 * ($attempt + 1)); // backoff: upstream occasionally hiccups
    }
    return null;
}

function cover_exists(string $url): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT      => UA,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return $code === 200;
}

/** Gutendex names are "Melville, Herman" → display as "Herman Melville". */
function display_name(string $raw): string {
    if (str_contains($raw, ', ')) {
        [$last, $first] = explode(', ', $raw, 2);
        return trim($first . ' ' . $last);
    }
    return $raw;
}

/** Map a gutendex record's subjects to VoiXLib categories (max 2). */
function map_categories(array $subjects, array $bookshelves): array {
    $hay = strtolower(implode(' | ', array_merge($subjects, $bookshelves)));
    $map = [
        'detective' => 'Mystery & Detective', 'mystery' => 'Mystery & Detective', 'crime' => 'Mystery & Detective',
        'science fiction' => 'Science Fiction', 'sci-fi' => 'Science Fiction',
        'fantasy' => 'Fantasy',
        'horror' => 'Gothic & Horror', 'gothic' => 'Gothic & Horror', 'ghost' => 'Gothic & Horror',
        'romance' => 'Romance',
        'adventure' => 'Adventure',
        'philosophy' => 'Philosophy', 'philosophers' => 'Philosophy',
        'history' => 'History', 'historical' => 'History',
        'short stories' => 'Short Stories',
        'poetry' => 'Poetry', 'poems' => 'Poetry',
        'classics' => 'Classics',
        'science' => 'Nature & Science', 'natural history' => 'Nature & Science', 'biology' => 'Nature & Science',
    ];
    $out = [];
    foreach ($map as $needle => $cat) {
        if (str_contains($hay, $needle)) $out[$cat] = true;
    }
    if (!$out && str_contains($hay, 'fiction')) $out['Classics'] = true;
    return array_slice(array_keys($out), 0, 2);
}

echo "Fetching Gutendex...\n";
$seen = []; // gutenberg id => book row

$queue = [];
// Popular overall first, then one popular page per category topic.
$queue[] = ['url' => GUTENDEX . '/?sort=popular&mime_type=text/html', 'cat' => null];
foreach ($categories as $label => $topic) {
    $queue[] = ['url' => GUTENDEX . '/?sort=popular&mime_type=text%2Fhtml&topic=' . rawurlencode($topic), 'cat' => $label];
}

foreach ($queue as $i => $job) {
    $data = http_json($job['url']);
    if (!$data || empty($data['results'])) { echo "  [skip] {$job['url']}\n"; continue; }
    foreach ($data['results'] as $r) {
        $gid = (int)($r['id'] ?? 0);
        if (!$gid || isset($seen[$gid])) continue;
        if (!empty($r['copyright']) && ($r['copyright'] ?? false) === true && str_contains(strtolower(implode(' ', $r['subjects'] ?? [])), 'recent')) continue;

        $author = '';
        $authorExtra = '';
        if (!empty($r['authors'][0]['name'])) {
            $author = display_name((string)$r['authors'][0]['name']);
            $life = $r['authors'][0]['birth_year'] ?? null;
            $death = $r['authors'][0]['death_year'] ?? null;
            $authorExtra = trim(($life ?? '') . '–' . ($death ?? ''), '–');
        }
        if ($author === '') continue; // anonymous texts: skip, weak catalog entries

        $cats = map_categories($r['subjects'] ?? [], $r['bookshelves'] ?? []);
        if ($job['cat'] && !in_array($job['cat'], $cats, true)) array_unshift($cats, $job['cat']);
        $cats = array_slice(array_unique($cats), 0, 2);

        $lang = $r['languages'][0] ?? 'en';
        if ($lang !== 'en') continue; // keep corpus English-first for the reader experience

        $summary = '';
        foreach (($r['summaries'] ?? []) as $s) { if (strlen($s) > 80) { $summary = trim($s); break; } }

        // publication year: only when Gutenberg states copyright, else leave null (no guessing)
        $year = null;

        $seen[$gid] = [
            'external_id'       => 'gutenberg:' . $gid,
            'source'            => 'gutenberg',
            'title'             => trim($r['title']),
            'author'            => $author,
            'author_life'       => $authorExtra ?: null,
            'description'       => $summary ?: null,
            'language'          => $lang,
            'publication_year'  => $year,
            'page_count'        => null,
            'isbn'              => null,
            'cover_url'         => 'https://www.gutenberg.org/cache/epub/' . $gid . '/pg' . $gid . '.cover.medium.jpg',
            'source_url'        => 'https://www.gutenberg.org/ebooks/' . $gid,
            'read_url'          => 'https://www.gutenberg.org/cache/epub/' . $gid . '/pg' . $gid . '-images.html',
            'downloads'         => (int)($r['download_count'] ?? 0),
            'subjects'          => array_slice(array_map('trim', $r['subjects'] ?? []), 0, 6),
            'categories'        => $cats,
            'readable'          => true,
        ];
    }
    echo '  total unique: ' . count($seen) . "\n";
    usleep(400000); // be polite
}

// ---- Enrichment pass (batched, concurrent via curl_multi) ------------------
echo "Verifying covers...\n";
$n = 0;
foreach ($seen as &$b) {
    $n++;
    if (!cover_exists($b['cover_url'])) {
        $b['cover_url'] = null; // dynamic SVG cover will be generated at render time
    }
    if ($n % 60 === 0) echo "  checked {$n}/" . count($seen) . "\n";
    usleep(60000);
}
unset($b);

/** Fetch many URLs concurrently; returns url => decoded json (or null). */
function http_json_multi(array $urls): array {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => UA,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[(int)$ch] = [$ch, $url];
    }
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.2);
    } while ($running > 0);
    $out = [];
    foreach ($handles as [$ch, $url]) {
        $body = curl_multi_getcontent($ch);
        $json = is_string($body) ? json_decode($body, true) : null;
        $out[$url] = is_array($json) ? $json : null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

$targets = [];
foreach ($seen as $key => $b) {
    if ($b['description'] && $b['page_count']) continue;
    $targets[$key] = OL_SEARCH . '?' . http_build_query([
        'title' => $b['title'], 'author' => $b['author'],
        'fields' => 'title,first_publish_year,number_of_pages_median,isbn,description,first_sentence',
        'limit' => 1,
    ]);
}
echo 'Enriching ' . count($targets) . " books via Open Library (batched)...\n";

/** Fill $seen[$key] from an Open Library doc if possible. */
function apply_ol_doc(array &$book, ?array $doc): void {
    if (!$doc) return;
    if (!$book['description']) {
        $desc = $doc['first_sentence'][0] ?? null;
        if (!$desc && isset($doc['description'])) {
            $desc = is_array($doc['description']) ? ($doc['description']['value'] ?? null) : $doc['description'];
        }
        if (is_string($desc) && strlen($desc) >= 60) $book['description'] = trim($desc);
    }
    if (!$book['page_count'] && !empty($doc['number_of_pages_median'])) $book['page_count'] = (int)$doc['number_of_pages_median'];
    if (!$book['isbn']) {
        foreach ((array)($doc['isbn'] ?? []) as $cand) {
            if (strlen((string)$cand) === 13 && str_starts_with((string)$cand, '978')) { $book['isbn'] = (string)$cand; break; }
        }
    }
    if (!$book['publication_year'] && !empty($doc['first_publish_year']) && (int)$doc['first_publish_year'] < 1970) {
        $book['publication_year'] = (int)$doc['first_publish_year'];
    }
}

function ol_url(array $b): string {
    return OL_SEARCH . '?' . http_build_query([
        'title' => $b['title'], 'author' => $b['author'],
        'fields' => 'title,first_publish_year,number_of_pages_median,isbn,description,first_sentence',
        'limit' => 1,
    ]);
}

// Open Library throttles hard: tiny batches, gentle pacing, backoff on failure.
for ($pass = 0; $pass < 3; $pass++) {
    $targets = [];
    foreach ($seen as $key => $b) {
        if (!$b['publication_year'] || !$b['page_count']) $targets[$key] = ol_url($b);
    }
    if (!$targets) break;
    if ($pass > 0) { echo "  pass " . ($pass + 1) . " for " . count($targets) . " remaining...\n"; }

    $successes = 0;
    foreach (array_chunk($targets, 2, true) as $batch) {
        $responses = http_json_multi(array_values($batch));
        $gotAny = false;
        foreach ($batch as $key => $url) {
            if (!empty($responses[$url]['docs'][0])) { apply_ol_doc($seen[$key], $responses[$url]['docs'][0]); $gotAny = true; }
        }
        if ($gotAny) $successes++;
        usleep(1100000);
    }
    if ($successes === 0 && $pass < 2) {
        echo "  upstream blocked — cooling down 75s\n";
        sleep(75);
    } else {
        echo "  pass done ($successes batches ok)\n";
        sleep(5);
    }
}
$withYear = 0;
foreach ($seen as $b) if ($b['publication_year']) $withYear++;
echo "  publication years resolved for {$withYear}/" . count($seen) . "\n";

$books = array_values($seen);
usort($books, fn($a, $b2) => $b2['downloads'] <=> $a['downloads']);
$books = array_slice($books, 0, 260);

$out = [
    '_meta' => [
        'generated_at' => gmdate('c'),
        'sources' => ['gutendex.com (Project Gutenberg metadata)', 'openlibrary.org (enrichment)'],
        'count' => count($books),
    ],
    'books' => $books,
];
file_put_contents(__DIR__ . '/../storage/seed/books.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Wrote " . count($books) . " books to storage/seed/books.json\n";
