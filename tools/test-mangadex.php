<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

assert(method_exists('MangaDexService', 'searchManga'), 'MangaDexService should exist and have searchManga');
assert(method_exists('MangaDexService', 'getChapters'), 'MangaDexService should exist and have getChapters');
assert(method_exists('MangaDexService', 'getChapterPages'), 'MangaDexService should exist and have getChapterPages');

echo "MangaDexService check OK\n";
