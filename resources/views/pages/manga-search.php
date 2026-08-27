<?php
/** Pencarian manga/manhwa/manhua (WeebCentral). */
component('icons');
?>
<div class="shell" style="padding:28px 0 60px">
  <header style="margin-bottom:24px">
    <h1 style="font-size:1.8rem;font-weight:800;margin:0 0 6px">Cari Manga &amp; Manhwa</h1>
    <p style="color:var(--ink-2);margin:0">Baca langsung di VoiXLib — tanpa pindah ke situs lain.</p>
  </header>

  <form class="manga-search-form" action="/manga/search" method="get" role="search">
    <label class="visually-hidden" for="mq">Cari judul</label>
    <span class="msf-ic"><?= icon('search', 18) ?></span>
    <input id="mq" type="search" name="q" value="<?= e($query) ?>" placeholder="Ketik judul… (contoh: Naruto, Solo Leveling, The King's Avatar)" autocomplete="off" maxlength="100" autofocus>
    <button class="btn btn-accent" type="submit"><?= icon('book-open', 16) ?> Cari</button>
  </form>

  <?php if ($query !== '' && empty($items)): ?>
    <div class="reader-empty-state" style="padding:48px 20px">
      <?= icon('search', 48) ?>
      <h2>Tidak ada hasil untuk “<?= e($query) ?>”</h2>
      <p>Coba judul lain atau kata kunci lebih pendek.</p>
    </div>
  <?php elseif (empty($items)): ?>
    <div class="reader-empty-state" style="padding:48px 20px">
      <?= icon('book-open', 48) ?>
      <h2>Mulai dengan mencari judul</h2>
      <p>Ketik nama manga, manhwa, atau manhua di atas.</p>
    </div>
  <?php else: ?>
    <p style="color:var(--ink-2);margin:18px 0 14px">Ditemukan <?= count($items) ?> hasil untuk “<?= e($query) ?>”</p>
    <div class="manga-grid">
      <?php foreach ($items as $i => $it): ?>
        <a class="manga-card vk-fade" style="animation-delay:<?= ($i * 45) ?>ms" href="<?= e($it['url_detail']) ?>">
          <div class="manga-card-cover cover-frame">
            <?php if (!empty($it['cover'])): ?>
              <img src="<?= e($it['cover']) ?>" alt="Sampul <?= e($it['title']) ?>" loading="lazy" referrerpolicy="no-referrer">
            <?php else: ?>
              <div class="manga-card-fallback"><?= e(mb_substr($it['title'], 0, 1)) ?></div>
            <?php endif; ?>
          </div>
          <div class="manga-card-title"><?= e($it['title']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
.manga-search-form { display:flex; align-items:center; gap:10px; background:var(--surface-2, rgba(255,255,255,.04)); border:1px solid var(--line, rgba(255,255,255,.1)); border-radius:12px; padding:10px 14px; max-width:760px; transition:border-color .15s, box-shadow .15s; }
.manga-search-form:focus-within { border-color:var(--accent,#6366f1); box-shadow:0 0 0 3px rgba(99,102,241,.18); }
.manga-search-form .msf-ic { color:var(--ink-2); flex:none; display:flex; }
.manga-search-form input { flex:1; background:none; border:none; outline:none; color:var(--ink-1,#e2e8f0); font-size:1rem; padding:6px 4px; }
.manga-search-form .btn { flex:none; }

.manga-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr)); gap:16px; }
.manga-card { text-decoration:none; color:var(--ink-1,#e2e8f0); background:var(--surface-2, rgba(255,255,255,.03)); border:1px solid var(--line, rgba(255,255,255,.08)); border-radius:12px; overflow:hidden; transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
.manga-card:hover { transform:translateY(-4px); border-color:var(--accent,#6366f1); box-shadow:0 14px 30px -16px var(--accent,#6366f1); }
.manga-card-cover { aspect-ratio:2/3; background:#0b0c10; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.manga-card-cover img { width:100%; height:100%; object-fit:cover; }
.manga-card-fallback { font-size:2.4rem; font-weight:800; color:var(--ink-2); }
.manga-card-title { padding:10px 12px; font-size:.9rem; font-weight:600; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
</style>
