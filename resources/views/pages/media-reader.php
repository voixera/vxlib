<?php
component('icons');
$user = Auth::user();
$activeCh = $chapters[$activeChIndex] ?? null;
?>
<div class="reader-view-container" id="reader-container">
  <div class="shell">
    <header class="reader-header">
      <div class="reader-header-left">
        <a class="btn btn-ghost btn-sm" href="<?= e($m['url_detail']) ?>"><?= icon('arrow-left', 16) ?> Kembali ke detail</a>
        <div class="reader-title-box">
          <h1 class="reader-manga-title"><?= e($m['title']) ?></h1>
          <?php if (!empty($activeCh)): ?>
            <p class="reader-ch-subtitle"><?= e($activeCh['title']) ?><?= !empty($activeCh['group']) ? ' · ' . e($activeCh['group']) : '' ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="reader-header-right">
        <button type="button" class="btn btn-ghost btn-sm" id="btn-fullscreen" aria-label="Layar penuh"><?= icon('grid', 16) ?> Fullscreen</button>
        <?php if ($user && !empty($bookId)): ?>
          <button type="button" class="btn btn-ghost btn-sm action-bookmark" data-book-id="<?= (int)$bookId ?>" aria-label="Bookmark chapter"><?= icon('bookmark', 16) ?> Bookmark</button>
        <?php endif; ?>
      </div>
    </header>

    <?php if (!empty($savedProgress) && $savedProgress['chapter'] > 0 && ($savedProgress['location'] ?? '') !== '' && $savedProgress['location'] !== $selectedChapterId): ?>
      <div class="resume-alert" id="resume-banner">
        <?= icon('clock', 18) ?>
        <span>Terakhir dibaca: Chapter <?= (int)$savedProgress['chapter'] ?>.</span>
        <a class="btn btn-solid btn-sm" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($savedProgress['location'])) ?>">Lanjutkan dari halaman terakhir</a>
        <button type="button" class="btn-close" onclick="document.getElementById('resume-banner').remove()">&times;</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($chapters)): ?>
      <div class="reader-grid">
        <!-- Sidebar Chapter Menu -->
        <aside class="reader-sidebar">
          <h3 class="reader-sidebar-title"><?= icon('list', 18) ?> Daftar Chapter (<?= count($chapters) ?>)</h3>
          <p class="reader-provider-tag">Sumber: <strong><?= e($providerName ?? 'MangaDex') ?></strong></p>
          <div class="reader-chapter-list">
            <?php foreach ($chapters as $index => $ch): ?>
              <a href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($ch['id'])) ?>"
                 class="reader-chapter-item<?= $ch['id'] === $selectedChapterId ? ' is-active' : '' ?>">
                <span class="ch-name"><?= e($ch['title']) ?></span>
                <span class="ch-meta">
                  <?php if (!empty($ch['language'])): ?><span class="chip-lang"><?= e($ch['language']) ?></span><?php endif; ?>
                  <?php if (!empty($ch['publish_date'])): ?><span class="ch-date"><?= e($ch['publish_date']) ?></span><?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </aside>

        <!-- Main Vertical Reader Area -->
        <main class="reader-main">
          <?php if (!empty($selectedChapterPages)): ?>
            <!-- Sticky Reading Progress Bar -->
            <div class="reading-progress-bar-wrap">
              <div class="reading-progress-bar" id="read-progress" style="width: 0%;"></div>
            </div>

            <div class="reader-chapter-indicator" style="text-align:center; padding: 12px 0; font-size:0.9rem; color:#94a3b8;">
              <span>Chapter <?= $activeChIndex + 1 ?> / <?= count($chapters) ?></span>
            </div>

            <div class="reader-pages-list" id="pages-container">
              <?php foreach ($selectedChapterPages as $index => $pageUrl): ?>
                <div class="page-wrapper skeleton-load" data-page-index="<?= $index + 1 ?>">
                  <img data-src="<?= e($pageUrl) ?>"
                       alt="Halaman <?= $index + 1 ?>"
                       class="manga-page-img lazy-page"
                       loading="lazy"
                       onload="this.parentElement.classList.remove('skeleton-load')"
                       onerror="this.parentElement.classList.remove('skeleton-load'); this.parentElement.classList.add('page-error');">
                  <span class="page-number-indicator"><?= $index + 1 ?> / <?= count($selectedChapterPages) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Chapter Navigation Controls -->
            <nav class="chapter-nav-bottom">
              <?php if ($prevChapter): ?>
                <a class="btn btn-ghost" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($prevChapter['id'])) ?>">
                  <?= icon('arrow-left', 16) ?> Chapter Sebelumnya
                </a>
              <?php else: ?>
                <span class="btn btn-ghost disabled"><?= icon('arrow-left', 16) ?> Chapter Pertama</span>
              <?php endif; ?>

              <a class="btn btn-ghost" href="<?= e($m['url_detail']) ?>">
                Kembali ke Detail
              </a>

              <?php if ($nextChapter): ?>
                <a class="btn btn-solid" href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($nextChapter['id'])) ?>">
                  Chapter Berikutnya <?= icon('arrow-right', 16) ?>
                </a>
              <?php else: ?>
                <span class="btn btn-ghost disabled">Chapter Terakhir</span>
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
    <?php else: ?>
      <div class="reader-empty-state">
        <?= icon('book-open', 64) ?>
        <h2>Chapter belum tersedia di provider VoiXLib.</h2>
        <p>Konten bacaan belum tersedia di provider chapter saat ini.</p>
        <a class="btn btn-solid" href="<?= e($m['source_url'] ?? ('https://anilist.co/manga/' . (int)$m['id'])) ?>" target="_blank" rel="noopener">
          <?= icon('share', 16) ?> Lihat sumber
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
.reader-view-container { background: #000; color: #fff; min-height: 100vh; padding: 0 0 60px; }
.reader-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 0; padding: 0; border-bottom: 3px solid #fff; flex-wrap: wrap; }
.reader-header-left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; padding: 12px 16px; }
.reader-title-box { display: flex; flex-direction: column; }
.reader-manga-title { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: 1.3rem; color: #fff; margin: 0; letter-spacing: -0.01em; }
.reader-ch-subtitle { font-size: 0.85rem; color: #b3aea0; margin: 3px 0 0; }
.reader-header-right { display: flex; align-items: center; gap: 0; border-left: 3px solid #fff; }

.resume-alert { display: flex; align-items: center; gap: 12px; background: var(--accent); color: #0E0E0C; border: 3px solid #fff; padding: 12px 18px; margin: 14px 16px 0; flex-wrap: wrap; }
.resume-alert .btn-close { margin-left: auto; background: none; border: none; color: #0E0E0C; font-size: 1.6rem; cursor: pointer; line-height: 1; }

.reader-grid { display: grid; grid-template-columns: 320px 1fr; gap: 0; align-items: start; }
@media (max-width: 900px) { .reader-grid { grid-template-columns: 1fr; } }

.reader-sidebar { border-right: 3px solid #fff; padding: 18px; max-height: 85vh; display: flex; flex-direction: column; background: #0E0E0C; }
.reader-sidebar-title { font-family: var(--mono); font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.85rem; color: #fff; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.reader-provider-tag { font-size: 0.78rem; color: #837e73; margin-bottom: 14px; }
.reader-chapter-list { overflow-y: auto; display: flex; flex-direction: column; gap: 0; padding-right: 0; }
.reader-chapter-item { display: flex; flex-direction: column; gap: 4px; padding: 12px 14px; border: 2px solid #fff; border-bottom: none; text-decoration: none; color: #cbd5e1; background: #000; }
.reader-chapter-list .reader-chapter-item:last-child { border-bottom: 2px solid #fff; }
.reader-chapter-item:hover { background: var(--accent); color: #0E0E0C; }
.reader-chapter-item.is-active { background: var(--accent); color: #0E0E0C; font-weight: 700; }
.reader-chapter-item .ch-name { font-size: 0.86rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reader-chapter-item .ch-meta { display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; opacity: .8; }
.chip-lang { border: 2px solid currentColor; padding: 1px 6px; font-weight: 700; }

.reader-main { display: flex; flex-direction: column; align-items: center; width: 100%; }
.reading-progress-bar-wrap { position: sticky; top: 0; width: 100%; height: 6px; background: #222; z-index: 50; }
.reading-progress-bar { height: 100%; background: var(--accent); transition: width 0.1s linear; }

.reader-pages-list { width: 100%; max-width: 900px; display: flex; flex-direction: column; align-items: center; gap: 0; margin-top: 0; }
.page-wrapper { width: 100%; min-height: 200px; position: relative; display: flex; justify-content: center; align-items: center; background: #111; border-bottom: 3px solid #fff; }
.page-wrapper.skeleton-load { background: linear-gradient(90deg, #111 25%, #1a1d24 50%, #111 75%); background-size: 200% 100%; animation: skeleton-wave 1.5s infinite; }
@keyframes skeleton-wave { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

.manga-page-img { width: 100%; max-width: 100%; height: auto; object-fit: contain; display: block; }
.page-number-indicator { position: absolute; bottom: 8px; right: 12px; background: #000; color: #fff; font-family: var(--mono); font-size: 0.72rem; padding: 3px 9px; border: 2px solid #fff; pointer-events: none; }

.chapter-nav-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; width: 100%; max-width: 900px; margin-top: 0; padding: 0; border-top: 3px solid #fff; }
.chapter-nav-bottom .btn { border-radius: 0; flex: 1; justify-content: center; border-left: none; border-right: none; }

/* reader buttons — hard edges, high contrast on black */
.reader-view-container .btn { border-radius: 0; }
.reader-view-container .btn-ghost { border-color: #fff; color: #fff; background: #000; }
.reader-view-container .btn-ghost:hover { border-color: var(--accent); background: var(--accent); color: #0E0E0C; }
.reader-view-container .btn-solid { background: var(--accent); color: #0E0E0C; border-color: #fff; }
.reader-view-container .btn-solid:hover { background: #fff; color: #0E0E0C; }
.reader-view-container .btn:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
.reader-view-container .icon-btn { border-color: #fff; color: #fff; border-radius: 0; }
.reader-view-container .icon-btn:hover { background: var(--accent); color: #0E0E0C; border-color: #fff; }
.reader-view-container .icon-btn:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }

.reader-empty-state { text-align: center; padding: 80px 20px; color: #b3aea0; }
.reader-empty-state h2 { font-family: var(--display); text-transform: uppercase; font-size: 1.4rem; color: #fff; margin: 16px 0 8px; }
.reader-empty-state p { margin-bottom: 24px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Lazy Loading Images
  var lazyImages = document.querySelectorAll('.lazy-page');
  if ('IntersectionObserver' in window) {
    var imageObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var image = entry.target;
          image.src = image.dataset.src;
          imageObserver.unobserve(image);
        }
      });
    }, { rootMargin: '300px 0px' });
    lazyImages.forEach(function (img) { imageObserver.observe(img); });
  } else {
    lazyImages.forEach(function (img) { img.src = img.dataset.src; });
  }

  // Reading Progress Bar
  window.addEventListener('scroll', function () {
    var totalHeight = document.documentElement.scrollHeight - window.innerHeight;
    var progress = totalHeight > 0 ? (window.scrollY / totalHeight) * 100 : 0;
    var bar = document.getElementById('read-progress');
    if (bar) bar.style.width = Math.min(100, Math.max(0, progress)) + '%';
  });

  // Fullscreen Toggle
  var fullscreenBtn = document.getElementById('btn-fullscreen');
  if (fullscreenBtn) {
    fullscreenBtn.addEventListener('click', function () {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(function (){});
      } else {
        document.exitFullscreen().catch(function (){});
      }
    });
  }

  // Keyboard Navigation
  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft') {
      var prevBtn = document.querySelector('.chapter-nav-bottom a[href*="ch="]:first-child');
      if (prevBtn) prevBtn.click();
    } else if (e.key === 'ArrowRight') {
      var nextBtn = document.querySelector('.chapter-nav-bottom a[href*="ch="]:last-child');
      if (nextBtn) nextBtn.click();
    }
  });

  // Save Progress to Supabase API
  var bookId = <?= json_encode($bookId ?? null) ?>;
  var selectedCh = <?= json_encode($selectedChapterId ?? null) ?>;
  var activeChNum = <?= json_encode($activeChIndex + 1) ?>;

  if (bookId && selectedCh) {
    var saveTimer = null;
    window.addEventListener('scroll', function () {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(function () {
        var totalHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = totalHeight > 0 ? Math.round((window.scrollY / totalHeight) * 100) : 0;
        
        fetch('/api/progress.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            book_id: bookId,
            progress: pct,
            chapter: activeChNum,
            location: selectedCh
          })
        }).catch(function (){});
      }, 2000);
    });
  }
});
</script>
