<?php component('icons'); ?>
<footer class="site-footer">
  <div class="shell footer-grid">
    <div class="footer-brand">
      <p class="footer-wordmark">Voi<b>X</b>Lib</p>
      <p class="footer-tag">Arsip &amp; pembaca manga + manhwa. Baca melampaui rak.</p>
    </div>
    <nav class="footer-col" aria-label="Katalog">
      <h3>Katalog</h3>
      <a href="/manga">Manga</a>
      <a href="/manhwa">Manhwa</a>
      <a href="/explore.php">Jelajahi</a>
      <a href="/search.php">Cari</a>
    </nav>
    <nav class="footer-col" aria-label="Akun">
      <h3>Akun</h3>
      <a href="/library.php">Perpustakaan</a>
      <a href="/bookmarks.php">Bookmark</a>
      <a href="/history.php">Riwayat</a>
      <a href="/settings.php">Pengaturan</a>
    </nav>
    <div class="footer-col footer-attrib">
      <h3>Sumber Data</h3>
      <p>Metadata manga &amp; manhwa dari
        <a href="https://anilist.co" rel="noopener" target="_blank">AniList</a> &amp; MangaDex.</p>
    </div>
  </div>
  <div class="shell footer-base">
    <span>© <?= date('Y') ?> VoiXLib</span>
    <span>MANGA + MANHWA · Dibangun dengan PHP &amp; SVG</span>
  </div>
</footer>

<nav class="mobile-nav" aria-label="Navigasi seluler">
  <?php
  $mnav = [
      ['/', 'Beranda', 'home', 'home'],
      ['/manga', 'Manga', 'manga', 'manga'],
      ['/manhwa', 'Manhwa', 'manhwa', 'manhwa'],
      ['/explore.php', 'Jelajah', 'compass', 'explore'],
      ['/bookmarks.php', 'Mark', 'bookmark', 'bookmarks'],
  ];
  $active = $activeNav ?? '';
  foreach ($mnav as [$href, $label, $icn, $key]):
      $isActive = $active === $key; ?>
    <a href="<?= e($href) ?>" class="<?= $isActive ? 'is-active' : '' ?>">
      <?= icon($icn, 21) ?><span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
