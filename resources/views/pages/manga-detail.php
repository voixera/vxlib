<?php
/** Detail seri + daftar chapter (WeebCentral). */
component('icons');
$series = $series;
$chapters = $chapters;
$firstChapter = $chapters[0] ?? null;
?>
<div class="shell" style="padding:28px 0 60px">
  <nav style="margin-bottom:18px"><a class="link link-info link-hover" href="/manga/search">&larr; Kembali ke pencarian</a></nav>

  <div class="manga-detail-layout">
    <aside class="manga-detail-cover">
      <?php if (!empty($series['cover'])): ?>
        <img src="<?= e($series['cover']) ?>" alt="Sampul <?= e($series['title']) ?>" referrerpolicy="no-referrer">
      <?php else: ?>
        <div class="manga-card-fallback" style="font-size:3rem"><?= e(mb_substr((string)($series['title'] ?? '?'), 0, 1)) ?></div>
      <?php endif; ?>
    </aside>

    <div class="manga-detail-info">
      <h1 style="font-size:1.9rem;font-weight:800;margin:0 0 8px"><?= e($series['title'] ?? 'Tanpa judul') ?></h1>

      <?php if (!empty($series['authors'])): ?>
        <p style="color:var(--ink-2);margin:0 0 12px">by <i><?= e(implode(', ', $series['authors'])) ?></i></p>
      <?php endif; ?>

      <div class="manga-meta-chips">
        <?php if (!empty($series['status'])): ?><span class="chip-lang"><?= e($series['status']) ?></span><?php endif; ?>
        <?php if (!empty($series['type'])): ?><span class="chip-lang"><?= e($series['type']) ?></span><?php endif; ?>
        <?php if (!empty($series['genres'])): foreach ($series['genres'] as $g): ?>
          <span class="chip-lang"><?= e($g) ?></span>
        <?php endforeach; endif; ?>
      </div>

      <?php if (!empty($series['description'])): ?>
        <p class="manga-detail-desc"><?= nl2br(e(mb_substr((string)$series['description'], 0, 600))) ?></p>
      <?php endif; ?>

      <?php if ($firstChapter): ?>
        <a class="btn btn-accent" href="/manga/read/<?= e($series['id']) ?>/<?= e($firstChapter['id']) ?>">
          <?= icon('book-open', 18) ?> Baca Chapter <?= e($firstChapter['title']) ?>
        </a>
      <?php else: ?>
        <div class="reader-empty-state" style="padding:18px 0">
          <?= icon('book-open', 28) ?>
          <p style="margin:0">Chapter tidak tersedia.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section style="margin-top:36px">
    <h2 style="font-size:1.3rem;font-weight:700;margin:0 0 14px;display:flex;align-items:center;gap:8px">
      <?= icon('list', 20) ?> Daftar Chapter
      <?php if (!empty($chapters)): ?><span style="font-size:.85rem;font-weight:400;color:var(--ink-2)"><?= count($chapters) ?> chapter</span><?php endif; ?>
    </h2>

    <?php if (empty($chapters)): ?>
      <div class="reader-empty-state" style="padding:40px 20px">
        <?= icon('book-open', 48) ?>
        <h3>Chapter tidak tersedia</h3>
        <p>Seri ini belum punya chapter di penyedia baca saat ini.</p>
      </div>
    <?php else: ?>
      <div class="manga-chapter-grid">
        <?php foreach ($chapters as $ch): ?>
          <a class="chapter-card" href="/manga/read/<?= e($series['id']) ?>/<?= e($ch['id']) ?>">
            <span class="ch-name"><?= e($ch['title']) ?></span>
            <?php if (!empty($ch['date'])): ?><span class="ch-date"><?= e(substr($ch['date'], 0, 10)) ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<style>
.manga-detail-layout { display:grid; grid-template-columns:220px 1fr; gap:28px; align-items:start; }
@media (max-width:760px){ .manga-detail-layout{ grid-template-columns:1fr; } }
.manga-detail-cover { border-radius:14px; overflow:hidden; background:#0b0c10; aspect-ratio:2/3; box-shadow:0 8px 30px rgba(0,0,0,.4); }
.manga-detail-cover img { width:100%; height:100%; object-fit:cover; }
.manga-meta-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
.manga-detail-desc { color:var(--ink-1,#cbd5e1); line-height:1.6; margin:0 0 18px; max-width:70ch; }
.manga-chapter-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:10px; max-height:520px; overflow-y:auto; padding-right:6px; }
.chapter-card { display:flex; flex-direction:column; gap:4px; padding:11px 14px; background:var(--surface-2, rgba(255,255,255,.03)); border:1px solid var(--line, rgba(255,255,255,.08)); border-radius:8px; text-decoration:none; color:var(--ink-1,#e2e8f0); transition:background .15s, border-color .15s; }
.chapter-card:hover { background:rgba(99,102,241,.12); border-color:var(--accent,#6366f1); }
.chapter-card .ch-name { font-size:.88rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chapter-card .ch-date { font-size:.74rem; color:var(--ink-2); }
</style>
