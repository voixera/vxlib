<?php component('icons'); ?>
<footer class="site-footer">
  <div class="shell footer-grid">
    <div class="footer-brand">
      <p class="footer-wordmark">Voi<b>X</b>Lib</p>
      <p class="footer-tag">Your library, beyond the shelf.</p>
    </div>
    <nav class="footer-col" aria-label="Site">
      <h3>Library</h3>
      <a href="/explore.php">Explore</a>
      <a href="/search.php">Search</a>
      <a href="/library.php">My library</a>
      <a href="/settings.php">Settings</a>
    </nav>
    <nav class="footer-col" aria-label="Project">
      <h3>Project</h3>
      <a href="/about.php">About</a>
      <a href="/privacy.php">Privacy</a>
      <a href="/terms.php">Terms</a>
    </nav>
    <div class="footer-col footer-attrib">
      <h3>Sources</h3>
      <p>Book metadata and reading copies from
        <a href="https://www.gutenberg.org" rel="noopener" target="_blank">Project Gutenberg</a> and
        <a href="https://gutendex.com" rel="noopener" target="_blank">Gutendex</a>.
        Enrichment and covers via <a href="https://openlibrary.org" rel="noopener" target="_blank">Open Library</a>.</p>
      <p>Public-domain texts. Thanks to the volunteers who digitize them.</p>
    </div>
  </div>
  <div class="shell footer-base">
    <span>© <?= date('Y') ?> VoiXLib</span>
    <span class="footer-mark-line">Set in editorial serif · built with PHP &amp; SVG</span>
  </div>
</footer>

<nav class="mobile-nav" aria-label="Mobile">
  <?php $mnav = [
      ['/', 'Home', 'home'],
      ['/explore.php', 'Explore', 'compass'],
      ['/library.php', 'Library', 'library'],
      ['/profile.php', 'Profile', 'user'],
      ['/settings.php', 'Settings', 'settings'],
  ];
  foreach ($mnav as [$href, $label, $icn]):
      $active = ($activeNav ?? '') !== '' && str_contains($href, ($activeNav === 'home' ? '/' : $activeNav)); ?>
    <a href="<?= e($href) ?>" class="<?= $active ? 'is-active' : '' ?>">
      <?= icon($icn, 21) ?><span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

