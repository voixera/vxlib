<?php
/**
 * Book cover renderer.
 * Real covers come from the catalog; when a book has none we generate a
 * deterministic editorial SVG from title/author/category — no generic placeholders.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

Security::bootSession();

$title  = mb_substr(trim((string)($_GET['t'] ?? 'Untitled')), 0, 120);
$author = mb_substr(trim((string)($_GET['a'] ?? '')), 0, 80);
$genre  = mb_substr(trim((string)($_GET['g'] ?? '')), 0, 40);

if ($title === '') { $title = 'Untitled'; }

// Deterministic palette per book.
$h = crc32(mb_strtolower($title . '|' . $author));
$palettes = [
    ['#B4531F', '#F4E9DC'], // sienna on parchment
    ['#2F5D50', '#EAEFE7'], // pine on mist
    ['#31456A', '#E7EBF2'], // ink blue on cloud
    ['#6C4A9E', '#EFEBF4'], // plum on lilac
    ['#8A3B12', '#F5E6D8'], // rust on sand
    ['#274046', '#E4EEEC'], // deep teal
    ['#7A1F2B', '#F4E4E4'], // oxblood
    ['#4A4A72', '#EAEBF2'], // slate violet
];
[$accent, $paper] = $palettes[$h % count($palettes)];
$pattern = abs($h >> 3) % 4; // 4 cover compositions
$ink = '#211F1C';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800, immutable');
header('X-Content-Type-Options: nosniff');

// Word-wrap the title into lines (max ~16 chars).
function wrap_title(string $t): array {
    $words = preg_split('/\s+/u', $t) ?: [$t];
    $lines = []; $cur = '';
    foreach ($words as $w) {
        if ($cur !== '' && mb_strlen($cur . ' ' . $w) > 15) { $lines[] = $cur; $cur = $w; }
        else $cur = $cur === '' ? $w : $cur . ' ' . $w;
        if (mb_strlen($cur) > 18) { $lines[] = mb_substr($cur, 0, 17); $cur = ''; }
    }
    if ($cur !== '') $lines[] = $cur;
    return array_slice($lines, 0, 4);
}
$lines = wrap_title($title);
$fontSize = count($lines) >= 3 ? 34 : 40;

$patterns = '';
$art = '';

switch ($pattern) {
    case 0: // horizon bands + sun disc
        $art .= '<circle cx="' . (150 + ($h % 60)) . '" cy="200" r="46" fill="' . $accent . '" opacity=".85"/>';
        for ($i = 0; $i < 5; $i++) {
            $y = 330 + $i * 42 - ($h >> $i) % 10;
            $op = 0.16 + $i * 0.09;
            $patterns .= '<path d="M0 ' . $y . ' Q 100 ' . ($y - 26 - ($h >> $i) % 22) . ' 200 ' . $y . ' T 400 ' . $y . ' V 600 H 0 Z" fill="' . $accent . '" opacity="' . number_format($op, 2) . '"/>';
        }
        break;
    case 1: // vertical rules + arch
        $patterns .= '<path d="M110 600 V300 a90 90 0 0 1 180 0 V600" fill="none" stroke="' . $accent . '" stroke-width="10" opacity=".9"/>';
        for ($i = 0; $i < 4; $i++) $patterns .= '<line x1="' . (36 + $i * 14) . '" y1="120" x2="' . (36 + $i * 14) . '" y2="480" stroke="' . $ink . '" stroke-width="2" opacity=".25"/>';
        break;
    case 2: // concentric rings
        for ($i = 3; $i >= 0; $i--) {
            $r = 30 + $i * 38;
            $patterns .= '<circle cx="200" cy="420" r="' . $r . '" fill="none" stroke="' . ($i === 1 ? $accent : $ink) . '" stroke-width="' . ($i === 1 ? 10 : 2.5) . '" opacity="' . ($i === 1 ? .95 : .3) . '"/>';
        }
        $patterns .= '<circle cx="200" cy="420" r="14" fill="' . $accent . '"/>';
        break;
    default: // diagonal ridge line-art
        $patterns .= '<path d="M-20 520 L140 360 L210 430 L320 320 L420 430 V600 H-20 Z" fill="' . $accent . '" opacity=".28"/>';
        $patterns .= '<path d="M-20 520 L140 360 L210 430 L320 320 L420 430" fill="none" stroke="' . $ink . '" stroke-width="3" opacity=".55"/>';
        $patterns .= '<circle cx="310" cy="170" r="34" fill="' . $accent . '" opacity=".8"/>';
}

$titleY = 130;
$eTitle = htmlspecialchars($title, ENT_XML1);
$eAuthor = htmlspecialchars($author, ENT_XML1);
$titleSvg = '';
foreach ($lines as $i => $line) {
    $titleSvg .= '<text x="44" y="' . ($titleY + $i * ($fontSize + 6)) . '" font-size="' . $fontSize . '" fill="' . $ink . '">' . htmlspecialchars($line, ENT_XML1) . '</text>';
}

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600" viewBox="0 0 400 600" role="img" aria-label="Cover of {$eTitle}">
  <defs>
    <style>
      text{font-family:'Iowan Old Style','Palatino Linotype',Georgia,serif;font-weight:700;letter-spacing:-.01em}
      .a{font-family:'Segoe UI',system-ui,sans-serif;font-weight:600;font-size:17px;letter-spacing:.14em;text-transform:uppercase}
      .p{font-family:'Segoe UI',system-ui,sans-serif;font-weight:500;font-size:13px;letter-spacing:.06em}
    </style>
  </defs>
  <rect width="400" height="600" fill="$paper"/>
  <rect x="10" y="10" width="380" height="580" fill="none" stroke="$ink" stroke-opacity=".28" stroke-width="1.5"/>
  <rect width="14" height="600" fill="$accent"/>
  $patterns
  $art
  $titleSvg
  <text class="a" x="44" y="64" fill="$accent">VoiXLib</text>
  <line x1="44" y1="76" x2="120" y2="76" stroke="$accent" stroke-width="3"/>
  <text class="p" x="44" y="560" fill="$ink" opacity=".78">{$eAuthor}</text>
</svg>
SVG;
