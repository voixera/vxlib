<?php
/** Navigasi atas — VoiXLib: Manga + Manhwa. */
component('brand');
component('icons');
$nav = [
    'home'      => ['/', 'Beranda', null],
    'manga'     => ['/manga', 'Manga', null],
    'manhwa'    => ['/manhwa', 'Manhwa', null],
    'explore'   => ['/explore.php', 'Jelajahi', 'compass'],
    'library'   => ['/library.php', 'Perpustakaan', 'library'],
    'bookmarks' => ['/bookmarks.php', 'Bookmark', 'bookmark'],
    'history'   => ['/history.php', 'Riwayat', 'clock'],
];
$active = $activeNav ?? '';
?>
<header class="site-header" id="site-header">
  <div class="shell header-inner">
    <a class="brand" href="/" aria-label="VoiXLib beranda">
      <?= voixlib_mark(30) ?>
      <span class="brand-text">Voi<b>X</b>Lib</span>
    </a>

    <form class="header-search" action="/search.php" method="get" role="search">
      <span class="hs-ic" aria-hidden="true"><?= icon('search', 16) ?></span>
      <input type="search" id="header-q" name="q" placeholder="CARI MANGA / MANHWA" aria-label="Cari manga atau manhwa" autocomplete="off">
      <kbd aria-hidden="true">/</kbd>
    </form>

    <nav class="main-nav" aria-label="Utama">
      <?php foreach ($nav as $key => [$href, $label, $icn]): ?>
        <a class="nav-link<?= $active === $key ? ' is-active' : '' ?>" href="<?= e($href) ?>">
          <?= $icn !== null ? icon($icn, 16) . ' ' : '' ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>

      <button class="icon-btn theme-toggle" type="button" data-theme-toggle
              aria-label="Ganti tema warna" style="border:none;border-left:var(--bd);border-right:var(--bd);width:56px"><?= icon('moon', 18) ?></button>

      <?php $u = Auth::user(); ?>
      <?php if ($u): ?>
        <a class="nav-avatar" href="/profile.php" aria-label="Profil kamu">
          <?php if (!empty($u['avatar_url'])): ?>
            <img src="<?= e($u['avatar_url']) ?>" alt="" width="34" height="34" referrerpolicy="no-referrer">
          <?php else: ?>
            <span class="avatar-fallback"><?= e(mb_strtoupper(mb_substr((string)$u['display_name'], 0, 1))) ?></span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <a class="btn btn-discord nav-cta" href="/auth/discord.php?next=<?= e(rawurlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>">
          <?= icon('discord', 16) ?> Masuk
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
