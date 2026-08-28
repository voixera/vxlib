<?php
/** Detail seri + daftar chapter (WeebCentral). */
component('icons');
$series = $series;
$chapters = $chapters;
$firstChapter = $chapters[0] ?? null;
?>
<div class="shell manga-detail-wrap">
  <nav style="margin-bottom:18px"><a class="shelf-more" href="/manga/search">&larr; Kembali ke pencarian</a></nav>

  <div class="manga-detail-layout">
    <aside class="manga-detail-cover">
      <?php if (!empty($series['cover'])): ?>
        <img src="<?= e($series['cover']) ?>" alt="Sampul <?= e($series['title']) ?>" referrerpolicy="no-referrer">
      <?php else: ?>
        <div class="manga-card-fallback"><?= e(mb_substr((string)($series['title'] ?? '?'), 0, 1)) ?></div>
      <?php endif; ?>
    </aside>

    <div>
      <h1 class="manga-detail-title"><?= e($series['title'] ?? 'Tanpa judul') ?></h1>

      <?php if (!empty($series['authors'])): ?>
        <p class="manga-detail-author">by <i><?= e(implode(', ', $series['authors'])) ?></i></p>
      <?php endif; ?>

      <div class="manga-meta-chips">
        <?php if (!empty($series['status'])): ?><span class="chip-lang"><?= e($series['status']) ?></span><?php endif; ?>
        <?php if (!empty($series['type'])): ?><span class="chip-lang"><?= e($series['type']) ?></span><?php endif; ?>
        <?php if (!empty($series['genres'])): foreach ($series['genres'] as $g): ?>
          <span class="chip-lang"><?= e($g) ?></span>
        <?php endforeach; endif; ?>
      </div>

      <?php if (!empty($series['description'])): ?>
        <p class="manga-detail-desc"><?= nl2br(e(mb_substr((string)$series['description'], 0, 600))) ?></p>
      <?php endif; ?>

      <?php if ($firstChapter): ?>
        <a class="btn btn-accent" href="/manga/read/<?= e($series['id']) ?>/<?= e($firstChapter['id']) ?>">
          <?= icon('book-open', 18) ?> Baca Sekarang
        </a>
      <?php else: ?>
        <div class="reader-empty-state" style="padding:18px 0">
          <?= icon('book-open', 28) ?>
          <p style="margin:0">Chapter tidak tersedia.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section class="manga-chapter-section">
    <div class="detail-chapters-head">
      <h2><?= icon('list', 20) ?> Daftar Chapter</h2>
      <?php if (!empty($chapters)): ?><span class="count"><?= count($chapters) ?> chapter</span><?php endif; ?>
    </div>

    <?php if (empty($chapters)): ?>
      <div class="reader-empty-state" style="padding:40px 20px">
        <?= icon('book-open', 48) ?>
        <h2>Chapter tidak tersedia</h2>
        <p>Seri ini belum punya chapter di penyedia baca saat ini.</p>
      </div>
    <?php else: ?>
      <div class="manga-chapter-grid">
        <?php foreach ($chapters as $i => $ch): ?>
          <a class="chapter-card" href="/manga/read/<?= e($series['id']) ?>/<?= e($ch['id']) ?>">
            <span class="chapter-num"><?= str_pad((int)$i + 1, 3, '0', STR_PAD_LEFT) ?></span>
            <span class="chapter-card-body">
              <span class="chapter-card-title"><?= e($ch['title']) ?></span>
              <?php if (!empty($ch['date'])): ?><span class="chapter-card-meta"><span><?= e(substr($ch['date'], 0, 10)) ?></span></span><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
