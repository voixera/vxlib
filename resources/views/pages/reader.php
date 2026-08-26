<?php
/** Pembaca flipbook — chromeless. Halaman dibangun oleh reader.js dari $chapters. */
component('icons');
?>
<div class="reader-progress-rail" aria-hidden="true"><i id="rp-bar"></i></div>

<div class="reader-shell" id="reader-shell" data-book-id="<?= e($bookId) ?>">
  <header class="reader-topbar">
    <a class="icon-btn" href="/klasik" aria-label="Kembali ke Klasik"><?= icon('arrow-left', 18) ?></a>
    <span class="rt-title"><?= e($title) ?> <span style="color:var(--ink-2)">· <?= e($author) ?></span></span>
    <button type="button" class="icon-btn" id="rd-toc-btn" aria-label="Daftar isi" aria-expanded="false"><?= icon('toc', 19) ?></button>
    <button type="button" class="icon-btn" id="rd-theme-btn" aria-label="Ganti tema baca"><?= icon('moon', 19) ?></button>
  </header>

  <div class="reader-body reader-flip-body">
    <div class="fb-skeleton" id="fb-skeleton" aria-hidden="true">
      <?php for ($i = 0; $i < 10; $i++): ?>
        <div class="skeleton skel-reader-p<?= $i % 4 === 3 ? ' short' : '' ?>" style="width:<?= 100 - ($i % 3) * 8 ?>%"></div>
      <?php endfor; ?>
      <div class="skeleton skel-cover" style="margin-top:18px;width:70%"></div>
    </div>

    <div id="flipbook" hidden>
      <div class="fb-stage" id="fb-stage">
        <div class="fb-book" id="fb-book"></div>
        <div class="fb-center" aria-hidden="true"><svg width="14" height="60" viewBox="0 0 14 60" fill="none" aria-hidden="true"><path d="M7 2c-3 12-3 44 0 56" stroke="currentColor" stroke-width="1.4" opacity=".5"/></svg></div>
        <button type="button" class="fb-corner fb-prev" id="fb-prev" aria-label="Halaman sebelumnya"><?= icon('arrow-left', 20) ?></button>
        <button type="button" class="fb-corner fb-next" id="fb-next" aria-label="Halaman berikutnya"><?= icon('arrow-right', 20) ?></button>
      </div>
      <div class="fb-status">
        <span id="fb-chapter"></span>
        <span id="fb-page-no"></span>
      </div>
    </div>

    <div id="reader-error" hidden style="padding-top:40px">
      <?php render_state('offline', 'Bacaan tidak bisa dimuat', 'Salinan teks sedang tidak tersedia. Kamu tetap bisa membacanya di sumber aslinya.'); ?>
      <div style="text-align:center">
        <a class="btn btn-accent" href="https://www.gutenberg.org/ebooks/<?= (int)$gbId ?>" target="_blank" rel="noopener"><?= icon('external', 16) ?> Buka di Project Gutenberg</a>
        <a class="btn btn-ghost" href="/klasik" style="margin-left:10px">Kembali</a>
      </div>
    </div>
  </div>

  <!-- Daftar isi -->
  <aside class="reader-settings-panel" id="toc-panel" role="dialog" aria-modal="true" aria-label="Daftar isi" hidden>
    <button type="button" class="icon-btn rs-close" id="toc-close" aria-label="Tutup daftar isi"><?= icon('close', 18) ?></button>
    <h3 style="font-size:17px;margin-bottom:14px">Daftar Isi</h3>
    <ul class="reader-toc-list" id="toc-list"></ul>
  </aside>
</div>

<script id="reader-data" type="application/json"><?= json_encode([
    'bookId'   => $bookId,
    'title'    => $title,
    'author'   => $author,
    'gbId'     => $gbId,
    'chapters' => $chapters,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
