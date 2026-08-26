<?php

/**
 * Sanity checks for MangaService (CLI):
 *   php tools/manga-check.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$fail = 0;
function check(bool $ok, string $what): void
{
    global $fail;
    echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n";
    if (!$ok) $fail++;
}

$ext = 'voixlib:manga:sakura-circuit';
$n = MangaService::pageCount($ext);
check($n >= 22 && $n <= 30 && $n % 2 === 0, "pageCount even & bounded ($n)");
check(MangaService::pageCount($ext) === $n, 'pageCount deterministic');

$chapters = MangaService::chapters($ext);
check(count($chapters) === (int)ceil($n / 10), 'chapter count matches pages');
$lastChapter = end($chapters);
check(count($lastChapter['pages']) > 0, 'last chapter has pages');

$allPages = [];
foreach ($chapters as $c) {
    check($c['start_page'] === count($allPages) + 1, "chapter {$c['index']} start_page aligned");
    foreach ($c['pages'] as $u) $allPages[] = $u;
}
check(count($allPages) === $n, 'pages cover whole book without gaps');

$p1 = MangaService::pageSvg($ext, 1);
$p5 = MangaService::pageSvg($ext, 5);
foreach ([1 => $p1, 5 => $p5] as $i => $svg) {
    check(str_starts_with($svg, '<svg') && str_ends_with($svg, '</svg>'), "page $i well-formed envelope");
    check(substr_count($svg, '<clipPath') === substr_count($svg, '</clipPath>'), "page $i clip tags balanced");
    check(substr_count($svg, '<g') <= substr_count($svg, '</g>') + 1, "page $i groups roughly balanced");
}
check($p1 === MangaService::pageSvg($ext, 1), 'page render deterministic');
check($p1 !== $p5, 'pages differ');

try {
    MangaService::pageSvg($ext, $n + 1);
    check(false, 'out-of-range page rejected');
} catch (InvalidArgumentException) {
    check(true, 'out-of-range page rejected');
}

exit($fail ? 1 : 0);
