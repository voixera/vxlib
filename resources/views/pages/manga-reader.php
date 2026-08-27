<?php
/** Reader vertikal: halaman asli, lazy load, skeleton, error, fullscreen, navigasi. */
component('icons');
$data = $data;
$series = $data['series'] ?? null;
$current = $data['current'] ?? null;
$chapters = $data['chapters'] ?? [];
$prev = $data['prev'] ?? null;
$next = $data['next'] ?? null;
$provider = $data['provider'] ?? '';
$seriesId = $series['id'] ?? '';
?>
<div class="mreader" id="mreader" data-series="<?= e($seriesId) ?>">
  <header class="mreader-top">
    <a class="icon-btn" href="/manga/detail/<?= e($seriesId) ?>" aria-label="Kembali ke detail"><?= icon('arrow-left', 18) ?></a>
    <div class="mreader-title">
      <span class="mrt-name"><?= e($series['title'] ?? 'Manga') ?></span>
      <?php if ($current): ?><span class="mrt-ch"><?= e($current['title']) ?></span><?php endif; ?>
    </div>

    <div class="mreader-tools">
      <select id="chapter-select" class="mreader-select" aria-label="Pilih chapter">
        <?php foreach ($chapters as $i => $ch): ?>
          <option value="/manga/read/<?= e($seriesId) ?>/<?= e($ch['id']) ?>"<?= ($current && $current['id'] === $ch['id']) ? ' selected' : '' ?>>
            <?= e($ch['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="icon-btn" id="mreader-fs" aria-label="Layar penuh"><?= icon('grid', 16) ?> FS</button>
    </div>
  </header>

  <div class="mreader-progress"><i id="mreader-bar"></i></div>

  <main class="mreader-pages" id="mreader-pages">
    <?php if (!$current): ?>
      <div class="reader-empty-state" style="padding:80px 20px">
        <?= icon('book-open', 56) ?>
        <h2>Chapter tidak tersedia</h2>
        <p>Seri ini belum memiliki chapter di penyedia baca.</p>
        <a class="btn btn-ghost" href="/manga/detail/<?= e($seriesId) ?>">Kembali ke detail</a>
      </div>
    <?php elseif (empty($current['pages'])): ?>
      <div class="reader-empty-state" style="padding:80px 20px">
        <?= icon('book-open', 56) ?>
        <h2>Halaman tidak dapat dimuat</h2>
        <p>Penyedia tidak mengembalikan halaman untuk chapter ini.</p>
        <a class="btn btn-ghost" href="<?= e($series['source_url'] ?? '/manga/detail/' . $seriesId) ?>" target="_blank" rel="noopener">Lihat di sumber</a>
      </div>
    <?php else: ?>
      <?php foreach ($current['pages'] as $idx => $url): ?>
        <figure class="mreader-page skeleton-load" data-index="<?= $idx + 1 ?>">
          <img class="mreader-img" data-src="<?= e($url) ?>" alt="Halaman <?= $idx + 1 ?>"
               referrerpolicy="no-referrer" decoding="async">
          <figcaption class="mreader-pageno"><?= $idx + 1 ?> / <?= count($current['pages']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <?php if ($current): ?>
  <nav class="mreader-nav">
    <?php if ($prev): ?>
      <a class="btn btn-ghost" href="/manga/read/<?= e($seriesId) ?>/<?= e($prev) ?>"><?= icon('arrow-left', 16) ?> Sebelumnya</a>
    <?php else: ?>
      <span class="btn btn-ghost disabled">Chapter pertama</span>
    <?php endif; ?>
    <a class="btn btn-ghost" href="/manga/detail/<?= e($seriesId) ?>">Daftar chapter</a>
    <?php if ($next): ?>
      <a class="btn btn-solid" href="/manga/read/<?= e($seriesId) ?>/<?= e($next) ?>">Berikutnya <?= icon('arrow-right', 16) ?></a>
    <?php else: ?>
      <span class="btn btn-ghost disabled">Chapter terakhir</span>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</div>

<script id="manga-reader-data" type="application/json"><?= json_encode([
    'seriesId' => $seriesId,
    'current'  => $current,
    'prev'     => $prev,
    'next'     => $next,
    'provider' => $provider,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<style>
.mreader { background:#000; color:#fff; min-height:100vh; }
.mreader-top { position:sticky; top:0; z-index:50; display:flex; align-items:center; gap:12px; padding:0; background:#0E0E0C; border-bottom:3px solid #fff; }
.mreader-title { flex:1; min-width:0; display:flex; flex-direction:column; line-height:1.2; padding:10px 4px; }
.mrt-name { font-family:var(--display); font-weight:800; text-transform:uppercase; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mrt-ch { font-size:.76rem; color:#b3aea0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mreader-tools { display:flex; align-items:center; gap:0; border-left:3px solid #fff; }
.mreader-select { background:#000; color:#fff; border:none; border-right:3px solid #fff; padding:10px 12px; max-width:42vw; font-size:.82rem; font-family:var(--mono); text-transform:uppercase; }
.mreader-progress { height:6px; background:#222; }
.mreader-progress i { display:block; height:100%; width:0; background:var(--accent); transition:width .1s linear; }
.mreader-pages { max-width:900px; margin:0 auto; padding:0 0 40px; display:flex; flex-direction:column; gap:0; }
.mreader-page { margin:0; position:relative; background:#111; min-height:200px; border-bottom:3px solid #fff; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.mreader-img { width:100%; height:auto; display:block; opacity:0; transform:translateY(8px); transition:opacity .4s ease, transform .4s ease; }
.mreader-page.page-error .mreader-img { display:none; }
.mreader-page.skeleton-load { background:linear-gradient(90deg,#111 25%,#1a1d24 50%,#111 75%); background-size:200% 100%; animation:skeleton-wave 1.5s infinite; }
.mreader-page.page-error { background:#15171c; }
.mreader-page.page-error::after { content:'Gambar gagal dimuat'; color:#b3aea0; font-size:.85rem; }
.mreader-img.is-loaded { opacity:1; transform:none; }
.mreader-pageno { position:absolute; bottom:8px; right:12px; background:#000; color:#fff; font-family:var(--mono); font-size:.72rem; padding:3px 9px; border:2px solid #fff; }
.mreader-nav { display:flex; align-items:center; justify-content:space-between; gap:0; max-width:900px; margin:0 auto; padding:0; flex-wrap:wrap; border-top:3px solid #fff; }
.mreader-nav .btn { border-radius:0; flex:1; justify-content:center; border-left:none; border-right:none; }
.mreader .btn { border-radius:0; }
.mreader .btn-ghost { border-color:#fff; color:#fff; background:#000; }
.mreader .btn-ghost:hover { border-color:var(--accent); background:var(--accent); color:#0E0E0C; }
.mreader .btn-solid { background:var(--accent); color:#0E0E0C; border-color:#fff; }
.mreader .btn-solid:hover { background:#fff; color:#0E0E0C; }
.mreader .icon-btn { border-color:#fff; color:#fff; border-radius:0; }
.mreader .icon-btn:hover { background:var(--accent); color:#0E0E0C; border-color:#fff; }
.mreader-select:focus-visible, .mreader .icon-btn:focus-visible { outline:3px solid var(--accent); outline-offset:2px; }
.reader-empty-state { text-align:center; color:#b3aea0; }
.reader-empty-state h2 { font-family:var(--display); text-transform:uppercase; color:#fff; font-size:1.3rem; margin:14px 0 8px; }
.reader-empty-state p { margin-bottom:20px; }
@keyframes skeleton-wave { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
@media (max-width:600px){ .mreader-select{ max-width:36vw; } .mrt-name{ font-size:.85rem; } }
</style>
