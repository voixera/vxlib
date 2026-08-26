<?php

declare(strict_types=1);

/** Catalog browsing + search. Server-renders the first page; JS refines via /api/books.php. */

final class ExploreController
{
    private BookRepository $books;

    public function __construct(?BookRepository $books = null)
    {
        $this->books = $books ?? new BookRepository();
    }

    /** @return array{params:array,result:array} */
    public static function paramsFromRequest(): array
    {
        $get = $_GET;
        $yearFromRaw = isset($get['year_from']) ? filter_var($get['year_from'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => (int)date('Y')]]) : false;
        $yearToRaw   = isset($get['year_to']) ? filter_var($get['year_to'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => (int)date('Y')]]) : false;

        $allowedCats = array_column(BookRepository::CATEGORIES, 'slug');
        $sorts = ['relevance', 'title_asc', 'title_desc', 'newest', 'oldest', 'popular'];

        return [
            'q'         => isset($get['q']) ? mb_substr(trim((string)$get['q']), 0, 120) : '',
            'category'  => in_array($get['category'] ?? '', $allowedCats, true) ? $get['category'] : '',
            'language'  => preg_match('/^[a-z]{2}$/', $get['language'] ?? '') ? $get['language'] : '',
            'year_from' => $yearFromRaw === false ? null : $yearFromRaw,
            'year_to'   => $yearToRaw === false ? null : $yearToRaw,
            'sort'      => in_array($get['sort'] ?? '', $sorts, true) ? $get['sort'] : 'relevance',
            'view'      => ($get['view'] ?? '') === 'list' ? 'list' : 'grid',
            'page'      => max(1, (int)($get['page'] ?? 1)),
            'per_page'  => 24,
        ];
    }

    public static function explore(): void
    {
        [$params] = [self::paramsFromRequest()];
        $repo = new BookRepository();
        $result = $repo->search($params);

        page('pages/explore', [
            'title'       => 'Explore the catalog — VoiXLib',
            'description' => 'Browse the full VoiXLib catalog: search, filter by category, language and era, and find your next read.',
            'activeNav'   => 'explore',
            'scripts'     => ['catalog.js'],
        ], [
            'params'      => $params,
            'result'      => $result,
            'categories'  => BookRepository::CATEGORIES,
        ]);
    }

    public static function searchPage(): void
    {
        // Search shares the catalog machinery but leads with the query box.
        $params = self::paramsFromRequest();
        if ($params['sort'] === 'relevance') $params['sort'] = 'popular';
        $result = (new BookRepository())->search($params);

        page('pages/search', [
            'title'       => ($params['q'] !== '' ? '“' . $params['q'] . '” — search' : 'Search') . ' — VoiXLib',
            'description' => 'Search across titles, authors, subjects and descriptions in the VoiXLib library.',
            'activeNav'   => 'explore',
            'scripts'     => ['catalog.js'],
        ], [
            'params'      => $params,
            'result'      => $result,
            'categories'  => BookRepository::CATEGORIES,
        ]);
    }
}
