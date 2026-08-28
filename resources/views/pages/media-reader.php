<?php
/** Pembaca manga — chromeless, editorial. Mode: Gulir Vertikal / Satu Halaman.
 *  Bahasa: pilih sumber chapter (ID/EN). Auto-scroll opsional. */
component('icons');
$user = Auth::user();
$activeCh = $chapters[$activeChIndex] ?? null;
$langs = $availableLangs ?? [];
?>
<div class="reader" id="reader" data-book-id="<?= e($bookId) ?>">
  <div class="reader-backdrop" id="reader-backdrop" hidden></div>

  <header class="reader-top">
    <div class="reader-top-left">
      <a class="icon-btn" href="<?= e($m['url_detail']) ?>" aria-label="Kembali ke detail"><?= icon('arrow-left', 18) ?></a>
      <div class="reader-title-box">
        <span class="reader-manga-title"><?= e($m['title']) ?></span>
        <?php if (!empty($activeCh)): ?>
          <span class="reader-ch-subtitle"><?= e($activeCh['title']) ?><?= !empty($activeCh['group']) ? ' · ' . e($activeCh['group']) : '' ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="reader-top-right">
      <button type="button" class="icon-btn reader-chapters-toggle" id="rd-toc-btn" aria-label="Daftar chapter" aria-expanded="false"><?= icon('toc', 19) ?></button>
      <?php if ($user && !empty($bookId)): ?>
        <button type="button" class="icon-btn action-bookmark" data-book-id="<?= (int)$bookId ?>" aria-label="Bookmark chapter"><?= icon('bookmark', 18) ?></button>
      <?php endif; ?>
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

    <?php if (count($langs) > 1): ?>
    <div class="rc-group">
      <span class="rc-label">Bahasa</span>
      <select class="reader-select" id="reader-lang" aria-label="Bahasa chapter">
        <option value="id" <?= $activeLang === 'id' ? 'selected' : '' ?>>Bahasa Indonesia</option>
        <option value="en" <?= $activeLang === 'en' ? 'selected' : '' ?>>English</option>
      </select>
    </div>
    <?php elseif (count($langs) === 1): ?>
      <div class="rc-group">
        <span class="rc-label">Bahasa</span>
        <span class="reader-select" style="border-style:dashed"><?= $langs[0] === 'id' ? 'Bahasa Indonesia' : 'English' ?></span>
      </div>
    <?php endif; ?>

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

  <?php if (!empty($indoUnavailable)): ?>
    <div class="reader-notice">Bahasa Indonesia belum tersedia untuk chapter ini. Menampilkan versi English.</div>
  <?php endif; ?>

  <?php if (!empty($savedProgress) && $savedProgress['chapter'] > 0 && ($savedProgress['location'] ?? '') !== '' && $savedProgress['location'] !== $selectedChapterId): ?>
    <div class="resume-alert" id="resume-banner">
      <?= icon('clock', 18) ?>
      <span>Terakhir dibaca: Chapter <?= (int)$savedProgress['chapter'] ?>.</span>
      <a class="btn btn-solid btn-sm" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($savedProgress['location'])) ?>">Lanjutkan</a>
      <button type="button" class="btn-close" onclick="document.getElementById('resume-banner').remove()">&times;</button>
    </div>
  <?php endif; ?>

  <div class="reader-progress-wrap" aria-hidden="true"><i id="read-progress"></i></div>

  <div class="reader-grid">
    <aside class="reader-sidebar" id="reader-sidebar" aria-label="Daftar chapter">
      <h3 class="reader-sidebar-title"><?= icon('list', 18) ?> Chapter (<?= count($chapters) ?>)</h3>
      <p class="reader-provider-tag">Sumber: <strong><?= e($providerName ?? 'MangaDex') ?></strong></p>
      <div class="reader-chapter-list">
        <?php foreach ($chapters as $index => $ch): ?>
          <a href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($ch['id'])) ?><?= count($langs) > 1 ? '&lang=' . e($activeLang) : '' ?>"
             class="reader-chapter-item<?= $ch['id'] === $selectedChapterId ? ' is-active' : '' ?>">
            <span class="ch-name"><?= e($ch['title']) ?></span>
            <span class="ch-meta">
              <?php if (!empty($ch['language'])): ?><span class="chip-lang"><?= e(strtoupper($ch['language'])) ?></span><?php endif; ?>
              <?php if (!empty($ch['publish_date'])): ?><span class="ch-date"><?= e($ch['publish_date']) ?></span><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>

    <main class="reader-main">
      <?php if (!empty($selectedChapterPages)): ?>
        <div class="reader-chapter-indicator">
          <span>Chapter <?= $activeChIndex + 1 ?> / <?= count($chapters) ?></span>
        </div>

        <div class="reader-pages" id="pages-container">
          <?php foreach ($selectedChapterPages as $index => $pageUrl): ?>
            <figure class="reader-page skeleton-load" data-page-index="<?= $index + 1 ?>">
              <img data-src="<?= e($pageUrl) ?>" alt="Halaman <?= $index + 1 ?>"
                   class="manga-page-img lazy-page" loading="lazy" referrerpolicy="no-referrer" decoding="async"
                   onload="this.parentElement.classList.remove('skeleton-load'); this.classList.add('is-loaded')"
                   onerror="this.parentElement.classList.remove('skeleton-load'); this.parentElement.classList.add('page-error')">
              <figcaption class="reader-pageno"><?= $index + 1 ?> / <?= count($selectedChapterPages) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>

        <nav class="reader-nav">
          <?php if ($prevChapter): ?>
            <a class="btn btn-ghost" id="ch-prev" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($prevChapter['id'])) ?><?= count($langs) > 1 ? '&lang=' . e($activeLang) : '' ?>">
              <?= icon('arrow-left', 16) ?> Sebelumnya
            </a>
          <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true"><?= icon('arrow-left', 16) ?> Chapter Pertama</span>
          <?php endif; ?>

          <a class="btn btn-ghost" href="<?= e($m['url_detail']) ?>">Daftar chapter</a>

          <?php if ($nextChapter): ?>
            <a class="btn btn-solid" id="ch-next" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($nextChapter['id'])) ?><?= count($langs) > 1 ? '&lang=' . e($activeLang) : '' ?>">
              Berikutnya <?= icon('arrow-right', 16) ?>
            </a>
          <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true">Chapter Terakhir</span>
          <?php endif; ?>
        </nav>
      <?php else: ?>
        <div class="reader-empty-state">
          <?= icon('alert-circle', 48) ?>
          <h2>Chapter sedang tidak dapat dimuat</h2>
          <p>Halaman untuk chapter ini gagal diisi oleh penyedia sumber. Coba beberapa saat lagi.</p>
          <a class="btn btn-ghost" href="<?= e($m['source_url'] ?? $m['url_detail']) ?>" target="_blank" rel="noopener">Lihat sumber resmi</a>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Lazy load
  var imgs = document.querySelectorAll('.lazy-page');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.src = e.target.dataset.src; obs.unobserve(e.target); }
      });
    }, { rootMargin: '400px 0px' });
    imgs.forEach(function (im) { io.observe(im); });
  } else {
    imgs.forEach(function (im) { im.src = im.dataset.src; });
  }

  // Fullscreen
  var fs = document.getElementById('btn-fullscreen');
  if (fs) fs.addEventListener('click', function () {
    if (!document.fullscreenElement) (document.documentElement.requestFullscreen || function(){}).call(document.documentElement).catch(function(){});
    else (document.exitFullscreen || function(){}).call(document).catch(function(){});
  });

  // Mobile chapter drawer
  var rd = document.getElementById('reader');
  var tog = document.getElementById('rd-toc-btn');
  var bd = document.getElementById('reader-backdrop');
  if (tog) tog.addEventListener('click', function () {
    var open = rd.classList.toggle('show-sidebar');
    tog.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (bd) bd.hidden = !open;
  });
  if (bd) bd.addEventListener('click', function () { rd.classList.remove('show-sidebar'); tog.setAttribute('aria-expanded','false'); bd.hidden = true; });

  // Language change → reload with chosen source
  var langSel = document.getElementById('reader-lang');
  if (langSel) langSel.addEventListener('change', function () {
    var u = new URL(location.href); u.searchParams.set('lang', langSel.value); location.href = u.toString();
  });

  // Progress + keyboard + mode + autoscroll handled by reader-ui.js
  if (window.VXReader) VXReader.init({
    bookId: <?= json_encode($bookId ?? null) ?>,
    chapter: <?= json_encode($activeChIndex + 1) ?>,
    location: <?= json_encode($selectedChapterId ?? null) ?>
  });
});
</script>
<script src="<?= asset('js/reader-ui.js') ?>" defer></script>
