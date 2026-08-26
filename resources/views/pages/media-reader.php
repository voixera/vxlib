<?php
component('icons');
$isAnime = $m['media_type'] === 'anime';
?>
<div class="shell">
  <section class="state-block">
    <div class="state-art" aria-hidden="true"><?= icon($isAnime ? 'play' : 'book-open', 72) ?></div>
    <p class="eyebrow"><?= $isAnime ? 'Ruang tonton' : 'Ruang baca' ?></p>
    <h1 class="state-heading"><?= $isAnime ? 'Menonton ' : 'Membaca ' ?><?= e($m['title']) ?></h1>
    <p class="state-body">Konten belum tersedia di VoiXLib. Halaman ini siap menampilkan <?= $isAnime ? 'episode' : 'chapter' ?> saat sumber berlisensi atau file milik situs tersambung.</p>
    <a class="btn btn-ghost" href="<?= e($m['url_detail']) ?>"><?= icon('arrow-left', 16) ?> Kembali ke detail</a>
  </section>
</div>
