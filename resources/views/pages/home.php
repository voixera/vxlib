<?php
/** Beranda — arsip manga + manhwa dari data nyata AniList. Section kosong tidak dirender. */
component('states');
component('icons');
component('motifs');
$heroCovers = $heroCovers ?? [];
$continue = [];
$progressMap = [];
if (!empty($continueReading)) {
    foreach ($continueReading as $b) {
        $b['url_detail'] = '/detail/' . ($b['media_type'] ?: 'manga') . '/' . preg_replace('/^anilist:/', '', (string)$b['external_id']);
        $b['type_label'] = ($b['media_type'] ?? '') === 'manhwa' ? 'Manhwa' : 'Manga';
        $continue[] = $b;
        $progressMap[$b['id']] = (int)($b['progress_pct'] ?? 0);
    }
}
?>
<section class="hero">
  <div class="shell hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">Manga &amp; Manhwa Archive</p>
      <h1 class="hero-title">Temukan cerita<br>berikutnya<em>.</em></h1>
      <p class="hero-lede">VoiXLib adalah platform dan arsip manga &amp; manhwa digital. Jelajahi karya curated,
        simpan ke perpustakaan pribadi, dan nikmati pengalaman membaca yang nyaman.</p>

      <div class="hero-actions">
        <form class="hero-search" action="/search.php" method="get" role="search">
          <span class="hs-ic" aria-hidden="true"><?= icon('search', 18) ?></span>
          <input type="search" name="q" placeholder="Cari manga atau manhwa…" aria-label="Cari" autocomplete="off">
          <button class="btn btn-accent" type="submit">Cari</button>
        </form>
        <a class="btn btn-ghost" href="/explore.php">Jelajahi katalog</a>
      </div>

      <?php if (!empty($stats)): ?>
        <div class="hero-stats" aria-label="Statistik">
          <?php foreach ($stats as $stat): ?>
            <div class="stat"><b><?= number_format((int)$stat['value']) ?></b><span><?= e($stat['label']) ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="hero-stage">
      <?php foreach (array_slice($heroCovers, 0, 3) as $i => $hb): ?>
        <a class="hero-cover <?= $i === 0 ? 'hero-feature' : ($i === 1 ? 'hero-sub' : 'hero-sub2') ?>" href="<?= e($hb['url_detail']) ?>" tabindex="-1" aria-hidden="true">
          <img src="<?= e($hb['cover_url']) ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>" decoding="async">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Genre ─────────────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <span class="section-num">Genre</span>
    <div class="genre-grid">
      <?php foreach (MediaNormalizer::genres() as $g): ?>
        <a class="cat-tile" href="/explore.php?genre=<?= e(rawurlencode($g)) ?>"><?= e($g) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($continue !== []): ?>
<section class="section" style="padding-top:0">
  <div class="shell">
    <?php view('components/shelf', ['heading' => 'Lanjutkan Membaca', 'books' => $continue, 'progress' => $progressMap]); ?>
  </div>
</section>
<hr class="section-rule">
<?php endif; ?>

<?php
$sections = [
    'trending' => ['Sedang Trending', '/explore.php?sort=trending'],
    'manga'    => ['Manga Populer', '/manga'],
    'manhwa'   => ['Manhwa Populer', '/manhwa'],
    'latest'   => ['Rilis Terbaru', '/explore.php?sort=newest'],
    'picks'    => ['Pilihan Minggu Ini', '/explore.php?sort=score'],
];
foreach ($sections as $key => [$heading, $href]):
    $shelf = $shelves[$key] ?? null;
    if (empty($shelf['items'])) continue; ?>
<section class="section" style="padding-top:0">
  <div class="shell">
    <?php view('components/shelf', ['heading' => $heading, 'books' => $shelf['items'], 'href' => $href]); ?>
  </div>
</section>
<?php endforeach; ?>

<?php if (!empty($recent['items'])): ?>
<section class="section" style="padding-top:0">
  <div class="shell">
    <?php view('components/shelf', ['heading' => 'Baru Ditambahkan', 'books' => $recent['items'], 'href' => '/explore.php?sort=newest']); ?>
  </div>
</section>
<?php endif; ?>

<section class="cta-band">
  <div class="shell cta-inner">
    <div>
      <span class="section-num">Perpustakaan</span>
      <h2>Simpan &amp; kelola koleksi</h2>
      <p>Masuk dengan Discord untuk menyinkronkan status bacaan — Ingin Dibaca, Sedang Dibaca, Selesai — di seluruh perangkatmu.</p>
    </div>
    <div class="cta-actions">
      <?php if (Auth::check()): ?>
        <a class="btn btn-solid" href="/library.php">Buka Perpustakaan</a>
      <?php else: ?>
        <a class="btn btn-discord" href="/auth/discord.php?next=%2Flibrary.php"><?= icon('discord', 18) ?> Masuk Discord</a>
        <a class="btn btn-ghost" href="/explore.php">Jelajahi dulu</a>
      <?php endif; ?>
    </div>
  </div>
</section>
