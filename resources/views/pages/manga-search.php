<?php
/** Pencarian manga/manhwa/manhua (WeebCentral). */
component('icons');
?>
<div class="shell manga-detail-wrap">
  <header class="page-head">
    <h1>Cari Manga &amp; Manhwa</h1>
    <p class="lede">Baca langsung di VoiXLib — tanpa pindah ke situs lain.</p>
  </header>

  <form class="manga-search-form" action="/manga/search" method="get" role="search">
    <label class="visually-hidden" for="mq">Cari judul</label>
    <span class="msf-ic" aria-hidden="true"><?= icon('search', 18) ?></span>
    <input id="mq" type="search" name="q" value="<?= e($query) ?>" placeholder="Ketik judul… (contoh: Naruto, Solo Leveling)" autocomplete="off" maxlength="100" autofocus>
    <button class="btn btn-accent" type="submit"><?= icon('book-open', 16) ?> Cari</button>
  </form>

  <?php if ($query !== '' && empty($items)): ?>
    <div class="reader-empty-state" style="padding:48px 20px">
      <?= icon('search', 48) ?>
      <h2>Tidak ada hasil untuk "<?= e($query) ?>"</h2>
      <p>Coba judul lain atau kata kunci lebih pendek.</p>
    </div>
  <?php elseif (empty($items)): ?>
    <div class="reader-empty-state" style="padding:48px 20px">
      <?= icon('book-open', 48) ?>
      <h2>Mulai dengan mencari judul</h2>
      <p>Ketik nama manga, manhwa, atau manhua di atas.</p>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:18px 0 4px">Ditemukan <?= count($items) ?> hasil untuk "<?= e($query) ?>"</p>
    <div class="manga-grid">
      <?php foreach ($items as $it): ?>
        <a class="manga-card" href="<?= e($it['url_detail']) ?>">
          <div class="manga-card-cover">
            <?php if (!empty($it['cover'])): ?>
              <img src="<?= e($it['cover']) ?>" alt="Sampul <?= e($it['title']) ?>" loading="lazy" referrerpolicy="no-referrer">
            <?php else: ?>
              <div class="manga-card-fallback"><?= e(mb_substr($it['title'], 0, 1)) ?></div>
            <?php endif; ?>
          </div>
          <div class="manga-card-title"><?= e($it['title']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
