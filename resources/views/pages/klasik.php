<?php
/** Klasik domain publik — teks nyata dari Project Gutenberg (Gutendex). */
component('states');
component('icons');
?>
<div class="shell">
  <header class="page-head reveal">
    <span class="section-num">Baca Penuh</span>
    <h1>Klasik Domain Publik</h1>
    <p class="lede">Karya klasik yang sudah bebas hak cipta — buka salah satunya dan nikmati
      pengalaman membaca dengan animasi membalik halaman. Data &amp; teks dari
      <a href="https://www.gutenberg.org" target="_blank" rel="noopener">Project Gutenberg</a>.</p>
  </header>

  <?php if (!$books): ?>
    <?php render_state('offline', 'Katalog klasik tidak bisa dihubungi', 'Layanan data sedang tidak menjawab. Coba lagi sebentar lagi.'); ?>
  <?php else: ?>
    <div class="catalog-grid" style="padding-bottom:30px">
      <?php foreach ($books as $b): view('components/book-card', ['book' => $b + ['author' => $b['author'] ?: 'Anonim']]); endforeach; ?>
    </div>
    <div class="load-more-wrap">
      <?php if ($nextPage): ?>
        <a class="btn btn-ghost" href="/klasik?page=<?= $nextPage ?>">Halaman berikutnya <?= icon('arrow-right', 16) ?></a>
      <?php endif; ?>
      <?php if ($page > 1): ?>
        <a class="btn btn-ghost" href="/klasik?page=<?= $page - 1 ?>"><?= icon('arrow-left', 16) ?> Sebelumnya</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
