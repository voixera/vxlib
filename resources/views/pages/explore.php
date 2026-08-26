<?php
/** Explore + search share this catalog template. */
component('states');
component('icons');

$books = $result['books'] ?? [];
$total = $result['total'];
$error = $result['error'] ?? null;
$hasFilters = ($params['q'] ?? '') !== '' || ($params['category'] ?? '') !== '' || ($params['language'] ?? '') !== ''
    || $params['year_from'] || $params['year_to'];
?>
<div class="shell">
  <header class="page-head reveal">
    <span class="section-num"><?= isset($isSearch) && $isSearch ? 'Search' : 'The catalog' ?></span>
    <h1><?= isset($isSearch) && $isSearch
        ? ($params['q'] !== '' ? 'Results for “<em>' . e($params['q']) . '</em>”' : 'Search the library')
        : 'Explore the catalog' ?></h1>
    <p class="lede">Real, public-domain editions. Filter by mood, language or era — then read right here in the browser.</p>
  </header>

  <form class="toolbar" id="catalog-toolbar" method="get" action="<?= e($_SERVER['PHP_SELF']) ?>">
    <label class="toolbar-label">
      <span class="visually-hidden">Category</span>
      <select name="category" data-autosubmit>
        <option value="">All categories</option>
        <?php foreach (($categories ?? []) as $cat): ?>
          <option value="<?= e($cat['slug']) ?>" <?= ($params['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <select name="language" data-autosubmit aria-label="Language">
      <option value="">Any language</option>
      <?php foreach (['en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'fi' => 'Finnish'] as $code => $label): ?>
        <option value="<?= $code ?>" <?= ($params['language'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <label class="toolbar-label">From
      <input type="number" name="year_from" min="-3000" max="<?= date('Y') ?>" placeholder="year"
             value="<?= $params['year_from'] ? (int)$params['year_from'] : '' ?>" style="width:86px" aria-label="From year">
    </label>
    <label class="toolbar-label">To
      <input type="number" name="year_to" min="-3000" max="<?= date('Y') ?>" placeholder="year"
             value="<?= $params['year_to'] ? (int)$params['year_to'] : '' ?>" style="width:86px" aria-label="To year">
    </label>
    <select name="sort" data-autosubmit aria-label="Sort order">
      <option value="relevance" <?= ($params['sort'] ?? '') === 'relevance' ? 'selected' : '' ?>>Relevance</option>
      <option value="popular" <?= ($params['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>Most read</option>
      <option value="title_asc" <?= ($params['sort'] ?? '') === 'title_asc' ? 'selected' : '' ?>>Title A–Z</option>
      <option value="title_desc" <?= ($params['sort'] ?? '') === 'title_desc' ? 'selected' : '' ?>>Title Z–A</option>
      <option value="newest" <?= ($params['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
      <option value="oldest" <?= ($params['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest</option>
    </select>
    <?php if (isset($params['q'])): ?><input type="hidden" name="q" value="<?= e($params['q']) ?>"><?php endif; ?>
    <div class="view-toggle" role="group" aria-label="View mode">
      <button type="button" data-view="grid" aria-pressed="<?= ($params['view'] ?? 'grid') === 'grid' ? 'true' : 'false' ?>" title="Grid view"><?= icon('grid', 18) ?></button>
      <button type="button" data-view="list" aria-pressed="<?= ($params['view'] ?? 'grid') === 'list' ? 'true' : 'false' ?>" title="List view"><?= icon('list', 18) ?></button>
    </div>
  </form>

  <div class="results-meta">
    <span id="results-count">
      <?php if ($error === null && $total !== null): ?>
        <?= number_format($total) ?> <?= $total === 1 ? 'book' : 'books' ?><?= $total > count($books) ? ' · page ' . (int)$params['page'] : '' ?>
      <?php endif; ?>
    </span>
    <?php if ($hasFilters): ?>
      <a href="<?= e(basename((string)$_SERVER['SCRIPT_NAME'])) ?>" id="clear-filters">Clear filters</a>
    <?php endif; ?>
  </div>

  <div id="catalog-results"
       class="<?= ($params['view'] ?? 'grid') === 'list' ? 'catalog-list' : 'catalog-grid' ?>"
       data-total="<?= $total !== null ? (int)$total : '' ?>">
    <?php if ($error === 'supabase_not_configured'): ?>
      <?php render_state('offline', 'The library isn’t connected yet',
          'This deployment still needs its Supabase credentials and seed data. See README → Setup.'); ?>
    <?php elseif ($error): ?>
      <?php render_state('offline', 'We lost the thread',
          'The catalog service didn’t answer in time. Nothing is broken on your side — try again.', '/'); ?>
    <?php elseif (!$books): ?>
      <?php if ($hasFilters): ?>
        <?php render_state('search', 'Nothing on this shelf',
            'No books match that combination of filters. Loosen one and the shelf will fill again.'); ?>
      <?php else: ?>
        <?php render_state('empty', 'The shelves are bare',
            'No books are in the catalog yet — run the seeder to fill them.'); ?>
      <?php endif; ?>
    <?php else: ?>
      <?php foreach ($books as $b):
          if (($params['view'] ?? 'grid') === 'list'): ?>
          <a class="book-row" href="/book.php?id=<?= e($b['external_id']) ?>">
            <span class="cover"><img src="<?= e($b['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $b['title'], 'a' => $b['author']]))) ?>" alt="" loading="lazy"></span>
            <span>
              <span class="row-title"><?= e($b['title']) ?></span>
              <span class="row-author"><?= e($b['author']) ?><?= $b['publication_year'] ? ' · ' . (int)$b['publication_year'] : '' ?></span>
              <?php if ($b['description']): ?><span class="row-excerpt"><?= e(mb_substr($b['description'], 0, 200)) ?>…</span><?php endif; ?>
            </span>
            <?php if ($b['readable']): ?><span class="chip is-active">Read</span><?php endif; ?>
          </a>
        <?php else:
          view('components/book-card', ['book' => $b]);
        endif;
      endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="load-more-wrap" id="load-more-wrap">
    <?php if ($error === null && $total !== null && count($books) < $total): ?>
      <button class="btn btn-ghost" id="load-more"
              data-next-page="<?= (int)$params['page'] + 1 ?>">Load more</button>
    <?php endif; ?>
  </div>
</div>


