<?php
/** Beranda — semua section dari data nyata AniList; section kosong tidak dirender. */
component('states');
component('icons');
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
  <div class="shell">
    <div class="hero-grid">
      <div>
        <p class="hero-kicker">Perpustakaan digital untuk penikmat cerita</p>
        <h1 class="hero-title">Temukan cerita<br>yang ingin kamu baca<em>.</em></h1>
        <p class="hero-lede">VoiXLib membantumu menemukan manga dan manhwa dari katalog
          dunia nyata — lengkap dengan genre, status, dan skor dari penyedia data. Simpan ke perpustakaan,
          tandai favoritmu, lanjutkan kapan pun.</p>

        <?php if (!empty($stats)): ?>
          <div class="hero-stats" aria-label="Statistik">
            <?php foreach ($stats as $stat): ?>
              <div class="stat"><b><?= number_format((int)$stat['value']) ?></b><span><?= e($stat['label']) ?></span></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="hero-stage">
        <div class="hero-covers" data-parallax-covers>
          <?php foreach ($heroCovers as $i => $hb): ?>
            <a class="hero-cover" href="<?= e($hb['url_detail']) ?>" style="animation-delay:-<?= $i * 2 ?>s" tabindex="-1" aria-hidden="true">
              <img src="<?= e($hb['cover_url']) ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>" decoding="async">
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Genre ─────────────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <span class="section-num">Genre</span>
    <div class="genre-grid">
      <?php foreach (MediaNormalizer::genres() as $g): ?>
        <a class="cat-tile reveal is-visible" href="/explore.php?genre=<?= e(rawurlencode($g)) ?>">
          <span class="cat-name"><?= e($g) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($continue !== []): ?>
<section class="section">
  <div class="shell">
    <?php view('components/shelf', ['heading' => 'Lanjutkan Membaca', 'books' => $continue, 'progress' => $progressMap]); ?>
  </div>
</section>
<hr class="section-rule">
<?php endif; ?>

<?php
$sections = [
    'latest'   => ['Rilis Komik Terbaru (Non-Lisensi / Scans)', '/explore.php?sort=newest'],
    'trending' => ['Sedang Trending', '/explore.php?sort=trending'],
    'manga'    => ['Manga Populer', '/manga'],
    'manhwa'   => ['Manhwa Populer', '/manhwa'],
    'picks'    => ['Pilihan Minggu Ini', '/explore.php?sort=score'],
];
foreach ($sections as $key => [$heading, $href]):
    $shelf = $shelves[$key] ?? null;
    if (empty($shelf['items'])) continue; ?>
<section class="section">
  <div class="shell">
    <?php view('components/shelf', ['heading' => $heading, 'books' => $shelf['items'], 'href' => $href]); ?>
  </div>
</section>
<?php endforeach; ?>

<?php if (!empty($recent['items'])): ?>
<section class="section">
  <div class="shell">
    <?php view('components/shelf', ['heading' => 'Baru Ditambahkan Musim Ini', 'books' => $recent['items'], 'href' => '/explore.php?sort=newest']); ?>
  </div>
</section>
<?php endif; ?>

<section class="section cta-band">
  <div class="shell cta-inner">
    <div>
      <span class="section-num">Mulai</span>
      <h2>Bangun rakitimu sendiri</h2>
      <p>Masuk dengan Discord untuk menyimpan judul ke Perpustakaan — Ingin Dibaca, Sedang Dibaca,
        Selesai — dan datanya tersinkron di semua perangkat.</p>
    </div>
    <div class="cta-actions">
      <?php if (Auth::check()): ?>
        <a class="btn btn-solid" href="/library.php">Buka Perpustakaan Saya</a>
      <?php else: ?>
        <a class="btn btn-discord" href="/auth/discord.php?next=%2Flibrary.php"><?= icon('discord', 18) ?> Masuk dengan Discord</a>
        <a class="btn btn-ghost" href="/explore.php">Jelajahi dulu saja</a>
      <?php endif; ?>
    </div>
  </div>
</section>
