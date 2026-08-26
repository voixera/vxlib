<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

assert(method_exists('MangaReaderApiService', 'getKomikIndoHome'), 'MangaReaderApiService should exist and have getKomikIndoHome');
assert(method_exists('MangaReaderApiService', 'searchKomikIndo'), 'MangaReaderApiService should exist and have searchKomikIndo');
assert(method_exists('MangaReaderApiService', 'getKomikIndoDetail'), 'MangaReaderApiService should exist and have getKomikIndoDetail');
assert(method_exists('MangaReaderApiService', 'getKomikIndoChapter'), 'MangaReaderApiService should exist and have getKomikIndoChapter');
assert(method_exists('MangaReaderApiService', 'searchMangabat'), 'MangaReaderApiService should exist and have searchMangabat');
assert(method_exists('MangaReaderApiService', 'getMangabatDetail'), 'MangaReaderApiService should exist and have getMangabatDetail');
assert(method_exists('MangaReaderApiService', 'getMangabatChapter'), 'MangaReaderApiService should exist and have getMangabatChapter');

echo "MangaReaderApiService check OK\n";
