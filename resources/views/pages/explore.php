<?php
/** Jelajah + pencarian — filter nyata dari provider (AniList). */
component('states');
component('icons');

$items = $result['items'] ?? [];
$total = $result['total'] ?? null;
$error = $result['error'] ?? null;
$typeLabels = ['manga' => 'Manga', 'manhwa' => 'Manhwa'];
$hasFilters = ($params['q'] ?? '') !== '' || $params['type'] !== '' || $params['genre'] !== ''
    || $params['status'] !== '' || $params['year_from'] || $params['year_to'];
?>
<div class="shell">
  <header class="page-head reveal">
    <span class="section-num"><?= !empty($isSearch) ? 'Pencarian' : 'Katalog' ?></span>
    <h1><?= !empty($isSearch)
        ? (($params['q'] ?? '') !== '' ? 'Hasil untuk “<em>' . e($params['q']) . '</em>”' : 'Cari di seluruh katalog')
        : ($params['type'] !== '' ? e($typeLabels[$params['type']]) . ' — Jelajahi' : 'Jelajahi Katalog') ?></h1>
    <p class="lede">Data judul, skor, dan status berasal langsung dari penyedia katalog. Pilih tipe,
      genre, dan tahun — lalu simpan favoritmu ke perpustakaan.</p>
  </header>

  <form class="toolbar" id="catalog-toolbar" method="get" action="<?= e(basename((string)$_SERVER['SCRIPT_NAME'])) ?>">
    <?php if (!empty($isSearch) && ($params['q'] ?? '') !== ''): ?>
      <input type="hidden" name="q" value="<?= e($params['q']) ?>">
    <?php endif; ?>
    <label class="toolbar-label">
      <span class="visually-hidden">Tipe</span>
      <select name="type" data-autosubmit>
        <option value="">Semua tipe</option>
        <?php foreach ($typeLabels as $slug => $label): ?>
          <option value="<?= e($slug) ?>" <?= $params['type'] === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="toolbar-label">
      <span class="visually-hidden">Genre</span>
      <select name="genre" data-autosubmit>
        <option value="">Semua genre</option>
        <?php foreach (MediaNormalizer::genres() as $g): ?>
          <option value="<?= e($g) ?>" <?= $params['genre'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="toolbar-label">
      <span class="visually-hidden">Status</span>
      <select name="status" data-autosubmit aria-label="Status">
        <option value="">Semua status</option>
        <option value="releasing" <?= $params['status'] === 'releasing' ? 'selected' : '' ?>>Sedang Berlangsung</option>
        <option value="finished" <?= $params['status'] === 'finished' ? 'selected' : '' ?>>Selesai</option>
        <option value="upcoming" <?= $params['status'] === 'upcoming' ? 'selected' : '' ?>>Belum Rilis</option>
        <option value="hiatus" <?= $params['status'] === 'hiatus' ? 'selected' : '' ?>>Hiatus</option>
      </select>
    </label>
    <label class="toolbar-label">Dari
      <input type="number" name="year_from" min="1910" max="<?= date('Y') ?>" placeholder="tahun"
             value="<?= $params['year_from'] ? (int)$params['year_from'] : '' ?>" style="width:86px" aria-label="Tahun mulai">
    </label>
    <label class="toolbar-label">Hingga
      <input type="number" name="year_to" min="1910" max="<?= date('Y') + 1 ?>" placeholder="tahun"
             value="<?= $params['year_to'] ? (int)$params['year_to'] : '' ?>" style="width:86px" aria-label="Tahun akhir">
    </label>
    <label class="toolbar-label">
      <span class="visually-hidden">Urutan</span>
      <select name="sort" data-autosubmit aria-label="Urutan">
        <option value="popular" <?= $params['sort'] === 'popular' ? 'selected' : '' ?>>Terpopuler</option>
        <option value="trending" <?= $params['sort'] === 'trending' ? 'selected' : '' ?>>Sedang Trending</option>
        <option value="score" <?= $params['sort'] === 'score' ? 'selected' : '' ?>>Rating Tertinggi</option>
        <option value="newest" <?= $params['sort'] === 'newest' ? 'selected' : '' ?>>Terbaru</option>
        <option value="oldest" <?= $params['sort'] === 'oldest' ? 'selected' : '' ?>>Terlama</option>
        <option value="title_asc" <?= $params['sort'] === 'title_asc' ? 'selected' : '' ?>>A–Z</option>
        <option value="title_desc" <?= $params['sort'] === 'title_desc' ? 'selected' : '' ?>>Z–A</option>
      </select>
    </label>
    <div class="view-toggle" role="group" aria-label="Mode tampilan">
      <button type="button" data-view="grid" aria-pressed="<?= $params['view'] === 'grid' ? 'true' : 'false' ?>" title="Tampilan grid"><?= icon('grid', 18) ?></button>
      <button type="button" data-view="list" aria-pressed="<?= $params['view'] === 'list' ? 'true' : 'false' ?>" title="Tampilan daftar"><?= icon('list', 18) ?></button>
    </div>
  </form>

  <div class="results-meta">
    <span id="results-count">
      <?php if ($error === null && $total !== null): ?>
        <?= number_format((int)$total) ?> judul<?= (int)$total > 24 && count($items) > 0 ? ' · halaman ' . (int)$params['page'] : '' ?>
      <?php endif; ?>
    </span>
    <?php if ($hasFilters): ?>
      <a href="<?= e(!empty($isSearch) ? '/search.php' : '/explore.php') ?>" id="clear-filters">Hapus filter</a>
    <?php endif; ?>
  </div>

  <div id="catalog-results"
       class="<?= $params['view'] === 'list' ? 'catalog-list' : 'catalog-grid' ?>"
       data-total="<?= $total !== null ? (int)$total : '' ?>">
    <?php if ($error === 'provider_unavailable'): ?>
      <?php render_state('offline', 'Katalog sedang tidak bisa dihubungi',
          'Penyedia data tidak menjawab tepat waktu. Ini bukan kesalahan perangkatmu — coba lagi sebentar lagi.'); ?>
    <?php elseif (!$items): ?>
      <?php if ($hasFilters || !empty($isSearch)): ?>
        <?php render_state('search', 'Rak ini masih kosong',
            'Tidak ada judul yang cocok dengan kombinasi filter itu. Longgarkan satu filter, dan rak akan terisi lagi.'); ?>
      <?php else: ?>
        <?php render_state('empty', 'Belum ada judul', 'Katalog belum menampilkan apa pun saat ini.'); ?>
      <?php endif; ?>
    <?php else: ?>
      <?php foreach ($items as $b):
          if ($params['view'] === 'list'): ?>
          <a class="book-row" href="<?= e($b['url_detail']) ?>">
            <span class="cover"><img src="<?= e($b['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $b['title'], 'a' => $b['author'], 'g' => $b['type_label']]))) ?>" alt="" loading="lazy"></span>
            <span>
              <span class="row-title"><?= e($b['title']) ?></span>
              <span class="row-author"><?= e($b['author']) ?><?= $b['year'] ? ' · ' . (int)$b['year'] : '' ?></span>
              <?php if ($b['description']): ?><span class="row-excerpt"><?= e(mb_substr((string)$b['description'], 0, 200)) ?>…</span><?php endif; ?>
            </span>
            <span class="chip is-active"><?= e($b['type_label']) ?></span>
          </a>
        <?php else:
          view('components/book-card', ['book' => $b]);
        endif;
      endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="load-more-wrap" id="load-more-wrap">
    <?php if ($error === null && !empty($result['has_next'])): ?>
      <button class="btn btn-ghost" id="load-more" data-next-page="<?= (int)$params['page'] + 1 ?>">Muat lebih banyak</button>
    <?php endif; ?>
  </div>
</div>
