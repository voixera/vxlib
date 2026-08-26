<?php
/** Riwayat baca — timestamp nyata dari Supabase. */
component('states');
component('icons');
?>
<div class="shell">
  <header class="page-head">
    <span class="section-num">Aktivitas</span>
    <h1>Riwayat Baca</h1>
    <p class="lede">Judul yang terakhir kamu buka, dengan waktu nyata dari akunmu.</p>
  </header>

  <?php if (!$history): ?>
    <?php render_state('empty', 'Belum ada riwayat', 'Buka halaman detail judul apa pun dan ia tercatat otomatis di sini.', '/explore.php', 'Jelajahi katalog'); ?>
  <?php else: ?>
    <div class="catalog-list" style="padding-bottom:80px">
      <?php foreach ($history as $h): $b = $h['book']; ?>
        <a class="book-row" href="/detail/<?= e($b['media_type'] ?? 'manga') ?>/<?= e(preg_replace('/^anilist:/', '', (string)$b['external_id'])) ?>">
          <span class="cover"><img src="<?= e($b['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $b['title'], 'a' => $b['author']]))) ?>" alt="" loading="lazy"></span>
          <span>
            <span class="row-title"><?= e($b['title']) ?></span>
            <span class="row-author"><?= e($b['author']) ?></span>
            <span class="row-excerpt"><?= icon('clock', 13) ?>
              Terakhir dibuka <?= e(date('j M Y, H:i', strtotime((string)$h['last_opened_at']))) ?> UTC</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
