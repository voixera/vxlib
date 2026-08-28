<?php
/** Pembaca WeebCentral — chromeless, editorial (shared .reader scope). */
component('icons');
$data = $data;
$series = $data['series'] ?? null;
$current = $data['current'] ?? null;
$chapters = $data['chapters'] ?? [];
$prev = $data['prev'] ?? null;
$langs = [];
foreach ($chapters as $c) { if (!empty($c['language'])) $langs[strtolower((string)$c['language'])] = true; }
$langs = array_keys($langs);
$next = $data['next'] ?? null;
$provider = $data['provider'] ?? '';
$seriesId = $series['id'] ?? '';
?>
<div class="reader" id="reader" data-book-id="">
  <div class="reader-backdrop" id="reader-backdrop" hidden></div>

  <header class="reader-top">
    <div class="reader-top-left">
      <a class="icon-btn" href="/manga/detail/<?= e($seriesId) ?>" aria-label="Kembali ke detail"><?= icon('arrow-left', 18) ?></a>
      <div class="reader-title-box">
        <span class="reader-manga-title"><?= e($series['title'] ?? 'Manga') ?></span>
        <?php if ($current): ?><span class="reader-ch-subtitle"><?= e($current['title']) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="reader-top-right">
      <button type="button" class="icon-btn reader-chapters-toggle" id="rd-toc-btn" aria-label="Daftar chapter" aria-expanded="false"><?= icon('toc', 19) ?></button>
      <button type="button" class="icon-btn" id="btn-fullscreen" aria-label="Layar penuh"><?= icon('grid', 16) ?></button>
    </div>
  </header>

  <div class="reader-controls">
    <div class="rc-group">
      <span class="rc-label">Mode</span>
      <div class="reader-seg" role="group" aria-label="Mode baca">
        <button type="button" data-mode="scroll" aria-pressed="true"><?= icon('list', 15) ?> Gulir</button>
        <button type="button" data-mode="comic" aria-pressed="false"><?= icon('book-open', 15) ?> Halaman</button>
      </div>
    </div>
    <div class="rc-group" id="autoscroll-group">
      <span class="rc-label">Auto-scroll</span>
      <div class="reader-seg" role="group" aria-label="Kecepatan auto-scroll">
        <button type="button" data-speed="off" aria-pressed="true">Mati</button>
        <button type="button" data-speed="slow" aria-pressed="false">Lambat</button>
        <button type="button" data-speed="normal" aria-pressed="false">Normal</button>
        <button type="button" data-speed="fast" aria-pressed="false">Cepat</button>
      </div>
    </div>
  </div>

  <div class="reader-progress-wrap" aria-hidden="true"><i id="read-progress"></i></div>

  <div class="reader-grid">
    <aside class="reader-sidebar" id="reader-sidebar" aria-label="Daftar chapter">
      <h3 class="reader-sidebar-title"><?= icon('list', 18) ?> Chapter (<?= count($chapters) ?>)</h3>
      <p class="reader-provider-tag">Sumber: <strong><?= e($provider ?: 'WeebCentral') ?></strong></p>
      <div class="reader-chapter-list">
        <?php foreach ($chapters as $ch): ?>
          <a href="/manga/read/<?= e($seriesId) ?>/<?= e($ch['id']) ?>"
             class="reader-chapter-item<?= $current && ($ch['id'] ?? null) === ($current['id'] ?? null) ? ' is-active' : '' ?>">
            <span class="ch-name"><?= e($ch['title']) ?></span>
            <span class="ch-meta">
              <?php if (!empty($ch['language'])): ?><span class="chip-lang"><?= e(strtoupper($ch['language'])) ?></span><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>

    <main class="reader-main">
      <?php if (!$current): ?>
        <div class="reader-empty-state">
          <?= icon('book-open', 56) ?>
          <h2>Chapter tidak tersedia</h2>
          <p>Seri ini belum memiliki chapter di penyedia baca.</p>
          <a class="btn btn-ghost" href="/manga/detail/<?= e($seriesId) ?>">Kembali ke detail</a>
        </div>
      <?php elseif (empty($current['pages'])): ?>
        <div class="reader-empty-state">
          <?= icon('book-open', 56) ?>
          <h2>Halaman tidak dapat dimuat</h2>
          <p>Penyedia tidak mengembalikan halaman untuk chapter ini.</p>
          <a class="btn btn-ghost" href="/manga/detail/<?= e($seriesId) ?>" target="_blank" rel="noopener">Lihat di sumber</a>
        </div>
      <?php else: ?>
        <div class="reader-chapter-indicator"><span>Chapter</span></div>
        <div class="reader-pages" id="pages-container">
          <?php foreach ($current['pages'] as $idx => $url): ?>
            <figure class="reader-page skeleton-load" data-page-index="<?= $idx + 1 ?>">
              <img data-src="<?= e($url) ?>" alt="Halaman <?= $idx + 1 ?>" class="manga-page-img lazy-page"
                   referrerpolicy="no-referrer" decoding="async" loading="lazy"
                   onload="this.parentElement.classList.remove('skeleton-load'); this.classList.add('is-loaded')"
                   onerror="this.parentElement.classList.remove('skeleton-load'); this.parentElement.classList.add('page-error')">
              <figcaption class="reader-pageno"><?= $idx + 1 ?> / <?= count($current['pages']) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>

        <nav class="reader-nav">
          <?php if ($prev): ?>
            <a class="btn btn-ghost" id="ch-prev" href="/manga/read/<?= e($seriesId) ?>/<?= e($prev) ?>"><?= icon('arrow-left', 16) ?> Sebelumnya</a>
          <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true"><?= icon('arrow-left', 16) ?> Chapter Pertama</span>
          <?php endif; ?>
          <a class="btn btn-ghost" href="/manga/detail/<?= e($seriesId) ?>">Daftar chapter</a>
          <?php if ($next): ?>
            <a class="btn btn-solid" id="ch-next" href="/manga/read/<?= e($seriesId) ?>/<?= e($next) ?>">Berikutnya <?= icon('arrow-right', 16) ?></a>
          <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true">Chapter Terakhir</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var imgs = document.querySelectorAll('.lazy-page');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.src = e.target.dataset.src; obs.unobserve(e.target); } });
    }, { rootMargin: '400px 0px' });
    imgs.forEach(function (im) { io.observe(im); });
  } else { imgs.forEach(function (im) { im.src = im.dataset.src; }); }

  var fs = document.getElementById('btn-fullscreen');
  if (fs) fs.addEventListener('click', function () {
    if (!document.fullscreenElement) (document.documentElement.requestFullscreen || function(){}).call(document.documentElement).catch(function(){});
    else (document.exitFullscreen || function(){}).call(document).catch(function(){});
  });

  var rd = document.getElementById('reader');
  var tog = document.getElementById('rd-toc-btn');
  var bd = document.getElementById('reader-backdrop');
  if (tog) tog.addEventListener('click', function () {
    var open = rd.classList.toggle('show-sidebar');
    tog.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (bd) bd.hidden = !open;
  });
  if (bd) bd.addEventListener('click', function () { rd.classList.remove('show-sidebar'); tog.setAttribute('aria-expanded','false'); bd.hidden = true; });

  if (window.VXReader) VXReader.init({});
});
</script>
