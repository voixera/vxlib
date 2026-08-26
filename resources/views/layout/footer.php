<?php component('icons'); ?>
<footer class="site-footer">
  <div class="shell footer-grid">
    <div class="footer-brand">
      <p class="footer-wordmark">Voi<b>X</b>Lib</p>
      <p class="footer-tag">Perpustakaanmu, melampaui rak.</p>
    </div>
    <nav class="footer-col" aria-label="Katalog">
      <h3>Katalog</h3>
      <a href="/anime">Anime</a>
      <a href="/manga">Manga</a>
      <a href="/manhwa">Manhwa</a>
      <a href="/explore.php">Jelajahi</a>
    </nav>
    <nav class="footer-col" aria-label="Akun">
      <h3>Akun</h3>
      <a href="/library.php">Perpustakaan Saya</a>
      <a href="/bookmarks.php">Bookmark</a>
      <a href="/history.php">Riwayat</a>
      <a href="/settings.php">Pengaturan</a>
    </nav>
    <div class="footer-col footer-attrib">
      <h3>Sumber Data</h3>
      <p>Metadata anime, manga &amp; manhwa dari
        <a href="https://anilist.co" rel="noopener" target="_blank">AniList</a>.
        VoiXLib adalah platform discovery — bacaan diarahkan ke sumber resmi dan berlisensi.</p>
    </div>
  </div>
  <div class="shell footer-base">
    <span>© <?= date('Y') ?> VoiXLib</span>
    <span class="footer-mark-line">Diset dalam serif editorial · dibangun dengan PHP &amp; SVG</span>
  </div>
</footer>

<nav class="mobile-nav" aria-label="Navigasi seluler">
  <?php
  $mnav = [
      ['/', 'Beranda', 'home'],
      ['/explore.php', 'Jelajahi', 'compass'],
      ['/bookmarks.php', 'Bookmark', 'bookmark'],
      ['/history.php', 'Riwayat', 'clock'],
      ['/profile.php', 'Profil', 'user'],
  ];
  foreach ($mnav as [$href, $label, $icn]):
      $active = ($activeNav ?? '') !== '' && str_contains($href, ($activeNav === 'home' ? '/' : $activeNav)); ?>
    <a href="<?= e($href) ?>" class="<?= $active ? 'is-active' : '' ?>">
      <?= icon($icn, 21) ?><span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
