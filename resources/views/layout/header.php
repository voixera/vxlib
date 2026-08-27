<?php
/** Navigasi atas. */
component('brand');
component('icons');
$nav = [
    'home'    => ['/', 'Beranda', 'home'],
    'manga'   => ['/manga', 'Manga', null],
    'manhwa'  => ['/manhwa', 'Manhwa', null],
    'baca'    => ['/manga/search', 'Baca', null],
    'explore' => ['/explore.php', 'Jelajahi', 'compass'],
];
?>
<header class="site-header" id="site-header">
  <div class="shell header-inner">
    <a class="brand" href="/" aria-label="VoiXLib beranda">
      <?= voixlib_mark(30) ?>
      <span class="brand-text">Voi<b>X</b>Lib</span>
    </a>

    <form class="header-search" action="/search.php" method="get" role="search">
      <label class="visually-hidden" for="header-q">Cari judul</label>
      <?= icon('search', 18) ?>
      <input id="header-q" type="search" name="q" placeholder="Cari manga, manhwa…"
             value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" maxlength="100">
      <kbd aria-hidden="true">/</kbd>
    </form>

    <nav class="main-nav" aria-label="Utama">
      <?php foreach ($nav as $key => [$href, $label, $icn]): ?>
        <a class="nav-link<?= ($activeNav ?? '') === $key ? ' is-active' : '' ?>" href="<?= e($href) ?>">
          <?= $icn !== null ? icon($icn, 17) : '' ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>

      <button class="icon-btn theme-toggle" type="button" data-theme-toggle
              aria-label="Ganti tema warna"><?= icon('moon', 18) ?></button>

      <?php $u = Auth::user(); ?>
      <?php if ($u): ?>
        <a class="nav-avatar" href="/profile.php" aria-label="Profil kamu">
          <?php if (!empty($u['avatar_url'])): ?>
            <img src="<?= e($u['avatar_url']) ?>" alt="" width="32" height="32" referrerpolicy="no-referrer">
          <?php else: ?>
            <span class="avatar-fallback"><?= e(mb_strtoupper(mb_substr((string)$u['display_name'], 0, 1))) ?></span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <a class="btn btn-discord nav-cta" href="/auth/discord.php?next=<?= e(rawurlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>">
          <?= icon('discord', 17) ?> Masuk
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
