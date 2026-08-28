<?php
/** Detail judul: metadata nyata dari AniList. */
component('icons');
component('states');

$typeLabels = ['manga' => 'Manga', 'manhwa' => 'Manhwa'];
$u = Auth::user();
?>
<div class="detail-banner" style="<?= !empty($m['banner_url']) ? '--banner:url(' . e($m['banner_url']) . ')' : '' ?>" aria-hidden="true"></div>

<div class="shell">
  <div class="book-layout detail-media">
    <aside class="detail-cover-wrap reveal is-visible">
      <div class="detail-cover<?= empty($m['cover_url']) ? ' is-generated' : '' ?>">
        <img src="<?= e($m['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $m['title'], 'a' => $m['author'], 'g' => $m['type_label']]))) ?>"
             alt="Sampul <?= e($m['title']) ?>" width="400" height="600">
      </div>
      <?php if (!empty($m['alt_title'])): ?>
        <p class="detail-source-note"><?= e($m['alt_title']) ?></p>
      <?php endif; ?>
    </aside>

    <div class="reveal is-visible">
      <span class="detail-crumb">
        <a href="/<?= e($m['media_type']) ?>"><?= e($typeLabels[$m['media_type']]) ?></a>
        <?php if ($m['status_label']): ?> · <?= e($m['status_label']) ?><?php endif; ?>
        <?php if ($m['year']): ?> · <?= (int)$m['year'] ?><?php endif; ?>
      </span>
      <h1 class="detail-title"><?= e($m['title']) ?></h1>
      <p class="detail-author">by <i><?= e($m['author']) ?></i><?php if (!empty($m['artist'])): ?>
        <span> · Art: <?= e($m['artist']) ?></span><?php endif; ?></p>

      <?php if (!empty($progress) && $u): ?>
        <div class="resume-banner">
          <?= icon('clock', 18) ?>
          <span>Terakhir dibuka · progress <strong><?= (int)$progress['progress'] ?>%</strong></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($m['genres'])): ?>
        <div class="subject-tags">
          <?php foreach ($m['genres'] as $g): ?>
            <a class="chip" href="/explore.php?genre=<?= e(rawurlencode($g)) ?>&type=<?= e($m['media_type']) ?>"><?= e($g) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <dl class="detail-facts">
        <?php if ($m['format_label']): ?><div class="fact"><dt>Format</dt><dd><?= e($m['format_label']) ?></dd></div><?php endif; ?>
        <?php if ($m['chapters'] !== null): ?><div class="fact"><dt>Chapter</dt><dd><?= (int)$m['chapters'] ?></dd></div><?php endif; ?>
        <?php if ($m['volumes'] !== null): ?><div class="fact"><dt>Volume</dt><dd><?= (int)$m['volumes'] ?></dd></div><?php endif; ?>
        <?php if ($m['score'] !== null): ?><div class="fact"><dt>Rating</dt><dd><?= number_format($m['score'] / 10, 1) ?> / 10</dd></div><?php endif; ?>
        <?php if ($m['popularity'] !== null): ?><div class="fact"><dt>Popularitas</dt><dd><?= number_format((int)$m['popularity']) ?> pengguna</dd></div><?php endif; ?>
        <div class="fact"><dt>Status</dt><dd><?= e($m['status_label'] ?? 'Tidak tersedia') ?></dd></div>
        <div class="fact"><dt>Tahun</dt><dd><?= $m['year'] ? (int)$m['year'] : 'Tidak tersedia' ?></dd></div>
      </dl>

       <div class="detail-actions">
        <?php if (!empty($wb['ok']) && !empty($wb['first'])): ?>
          <a class="btn btn-accent" href="/manga/read/<?= e($wb['seriesId']) ?>/<?= e($wb['first']) ?>">
            <?= icon('book-open', 18) ?> Baca Sekarang
          </a>
        <?php else: ?>
          <a class="btn btn-accent" href="/manga/search?q=<?= e(urlencode($m['title'])) ?>">
            <?= icon('book-open', 18) ?> Baca Sekarang
          </a>
        <?php endif; ?>

        <button type="button" class="btn btn-ghost action-library" data-book-id="<?= $bookId ?? '' ?>"
                data-status="<?= e($shelfStatus ?? '') ?>" <?= ($u && $bookId) ? '' : 'data-needs-auth="1"' ?>>
          <?= icon('library', 17) ?>
          <span class="al-label"><?= !empty($shelfStatus) ? 'Di rak kamu' : '+ Perpustakaan' ?></span>
        </button>

        <button type="button" class="icon-btn action-bookmark<?= !empty($hasBookmark) ? ' is-saved' : '' ?>"
                data-book-id="<?= $bookId ?? '' ?>" aria-pressed="<?= !empty($hasBookmark) ? 'true' : 'false' ?>"
                aria-label="Bookmark judul ini" <?= ($u && $bookId) ? '' : 'data-needs-auth="1"' ?>>
          <?= icon(!empty($hasBookmark) ? 'bookmark-filled' : 'bookmark', 19) ?>
        </button>

        <button type="button" class="icon-btn action-share"
                data-title="<?= e($m['title']) ?>" data-url="<?= e(url($m['url_detail'])) ?>"
                aria-label="Bagikan judul me ini">
          <?= icon('share', 18) ?>
        </button>
      </div>

      <?php if (!$migrationOk && SupabaseClient::configured()): ?>
        <p style="margin-top:12px;font-size:13px;color:var(--danger)">
          Penyimpanan perpustakaan belum aktif untuk judul ini (jalankan supabase/migration-002-media.sql).</p>
      <?php elseif (!$u): ?>
        <p style="margin-top:14px;font-size:13.5px;color:var(--ink-2)">
          <a href="/auth/discord.php?next=<?= e(rawurlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>">Masuk dengan Discord</a>
          untuk menyimpan judul ini ke rak pribadimu.</p>
      <?php endif; ?>

      <?php if (!empty($m['description'])): ?>
        <section class="detail-synopsis">
          <h2>Sinopsis</h2>
          <p class="detail-desc"><?= nl2br(e($m['description'])) ?></p>
          <p class="synopsis-credit">Sinopsis &amp; data © AniList — diterjemahkan otomatis oleh penyedia bila tersedia.</p>
        </section>
      <?php else: ?>
        <section class="detail-synopsis">
          <h2>Sinopsis</h2>
          <p class="detail-desc" style="font-style:italic;color:var(--ink-2)">Tidak tersedia.</p>
        </section>
      <?php endif; ?>

       <!-- Daftar Chapter -->
       <section class="detail-chapters">
         <div class="detail-chapters-head">
           <h2><?= icon('list', 18) ?> Daftar Chapter</h2>
           <?php if (!empty($chapters)): ?>
             <span class="count"><span id="chapter-count"><?= count($chapters) ?></span> chapter</span>
           <?php endif; ?>
         </div>

         <?php if (!empty($chapters)):
           $langs = [];
           foreach ($chapters as $ch) { if (!empty($ch['language'])) $langs[$ch['language']] = true; }
           $langs = array_keys($langs);
         ?>
           <div class="chapter-tools">
             <input type="search" class="ct-search" id="chapter-search" placeholder="Cari chapter…" aria-label="Cari chapter" autocomplete="off">
             <select class="ct-sort" id="chapter-sort" aria-label="Urutkan chapter">
               <option value="desc">Terbaru dulu</option>
               <option value="asc">Terlama dulu</option>
               <option value="az">Judul A–Z</option>
             </select>
             <?php if (count($langs) > 1): ?>
               <select class="ct-lang" id="chapter-lang" aria-label="Bahasa chapter">
                 <option value="">Semua bahasa</option>
                 <?php foreach ($langs as $lg): ?>
                   <option value="<?= e($lg) ?>"><?= e(strtoupper($lg)) ?></option>
                 <?php endforeach; ?>
               </select>
             <?php endif; ?>
           </div>
            <div class="chapter-grid" id="chapter-grid">
             <?php foreach ($chapters as $ci => $ch): ?>
               <a href="<?= e($ch['url'] ?? '#') ?>" class="chapter-card"
                  data-title="<?= e(strtolower((string)($ch['title'] ?? ''))) ?>"
                  data-lang="<?= e(strtolower((string)($ch['language'] ?? ''))) ?>"
                  data-date="<?= e((string)($ch['publish_date'] ?? '')) ?>">
                 <span class="chapter-num"><?= str_pad((int)$ci + 1, 3, '0', STR_PAD_LEFT) ?></span>
                 <span class="chapter-card-body">
                   <span class="chapter-card-title"><?= e($ch['title']) ?></span>
                   <span class="chapter-card-meta">
                     <span class="chapter-lang"><?= e(strtoupper($ch['language'] ?? 'ID')) ?></span>
                     <?php if (!empty($ch['publish_date'])): ?>
                       <span><?= e($ch['publish_date']) ?></span>
                     <?php endif; ?>
                   </span>
                 </span>
               </a>
             <?php endforeach; ?>
           </div>
           <p class="empty-note" id="chapter-empty" style="display:none;margin-top:16px">Tidak ada chapter yang cocok.</p>
         <?php else: ?>
           <div class="chapter-empty">
             <p>Chapter belum tersedia di penyedia baca saat ini.</p>
             <a class="btn btn-ghost btn-sm" href="/manga/search?q=<?= e(urlencode($m['title'])) ?>">
               <?= icon('search', 14) ?> Cari di VoiXLib
             </a>
           </div>
         <?php endif; ?>
        </section>

        <?php if (!empty($chapters)): ?>
        <script>
        (function () {
          var grid = document.getElementById('chapter-grid');
          if (!grid) return;
          var q = document.getElementById('chapter-search');
          var sort = document.getElementById('chapter-sort');
          var lang = document.getElementById('chapter-lang');
          var empty = document.getElementById('chapter-empty');
          var count = document.getElementById('chapter-count');
          var cards = Array.prototype.slice.call(grid.querySelectorAll('.chapter-card'));
          function apply() {
            var term = (q.value || '').toLowerCase();
            var lg = (lang && lang.value || '').toLowerCase();
            var order = sort ? sort.value : 'desc';
            var visible = [];
            cards.forEach(function (c) {
              var ok = (!term || c.dataset.title.indexOf(term) !== -1) && (!lg || c.dataset.lang === lg);
              c.style.display = ok ? '' : 'none';
              if (ok) visible.push(c);
            });
            if (order === 'desc') visible.reverse();
            else if (order === 'az') visible.sort(function (a, b) { return a.dataset.title.localeCompare(b.dataset.title); });
            visible.forEach(function (c) { grid.appendChild(c); });
            if (empty) empty.style.display = visible.length ? 'none' : 'block';
            if (count) count.textContent = visible.length;
          }
          [q, sort, lang].forEach(function (el) { if (el) el.addEventListener('input', apply); });
          apply();
        })();
        </script>
        <?php endif; ?>
    </div>
  </div>
</div>
