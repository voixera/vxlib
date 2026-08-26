<?php
component('icons');
?>
<div class="shell">
  <header style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
      <a class="btn btn-ghost btn-sm" href="<?= e($m['url_detail']) ?>"><?= icon('arrow-left', 16) ?> Kembali ke detail</a>
      <h1 style="font-size: 1.6rem; font-weight: 700; margin-top: 8px; font-family: var(--font-display, inherit);"><?= e($m['title']) ?></h1>
    </div>
    <span class="chip"><?= strtoupper($m['media_type']) ?></span>
  </header>

  <?php if (!empty($chapters)): ?>
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start;">
      <!-- Sidebar Chapter Selector -->
      <aside style="background: var(--surface-2, rgba(255,255,255,0.03)); border: 1px solid var(--line, rgba(255,255,255,0.1)); border-radius: 12px; padding: 16px; max-height: 80vh; overflow-y: auto;">
        <h3 style="font-size: 1rem; margin-bottom: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
          <?= icon('list', 18) ?> Daftar Chapter (<?= count($chapters) ?>)
        </h3>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <?php foreach ($chapters as $ch): ?>
            <a href="/read/<?= e($m['media_type']) ?>/<?= (int)$m['id'] ?>?ch=<?= e(urlencode($ch['id'])) ?>"
               class="btn btn-ghost"
               style="text-align: left; justify-content: flex-start; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; font-size: 0.88rem; <?= $ch['id'] === $selectedChapterId ? 'background: var(--accent-subtle, rgba(99,102,241,0.2)); color: var(--accent, #6366f1); font-weight: 600;' : '' ?>">
              <?= e($ch['title']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>

      <!-- Main Reader Area -->
      <main style="background: var(--surface-1, #0d0e12); border: 1px solid var(--line, rgba(255,255,255,0.1)); border-radius: 12px; padding: 20px; text-align: center;">
        <?php if (!empty($selectedChapterPages)): ?>
          <div style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <?php foreach ($selectedChapterPages as $index => $pageUrl): ?>
              <img src="<?= e($pageUrl) ?>" alt="Halaman <?= $index + 1 ?>" loading="lazy" style="max-width: 100%; height: auto; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="padding: 40px 20px;">
            <p style="color: var(--ink-2); margin-bottom: 12px;">Gagal memuat halaman untuk chapter ini.</p>
            <p style="font-size: 0.85rem; color: var(--ink-3);">Pilih chapter lain pada daftar di samping.</p>
          </div>
        <?php endif; ?>
      </main>
    </div>
  <?php else: ?>
    <section class="state-block">
      <div class="state-art" aria-hidden="true"><?= icon('book-open', 72) ?></div>
      <p class="eyebrow">Ruang baca</p>
      <h1 class="state-heading">Membaca <?= e($m['title']) ?></h1>
      <?php if (!empty($art)): ?>
        <figure style="margin:20px auto;max-width:280px">
          <img src="<?= e($art['url']) ?>" alt="Ilustrasi dekoratif" width="280" height="280" loading="lazy" style="width:100%;height:auto;border-radius:12px">
          <figcaption style="margin-top:8px;font-size:12px;color:var(--ink-2)">Ilustrasi SFW dari <a href="https://nekosapi.com" rel="noopener">Nekos API</a><?= !empty($art['artist']) ? ' · ' . e($art['artist']) : '' ?></figcaption>
        </figure>
      <?php endif; ?>
      <p class="state-body">Chapter sedang disiapkan oleh penyedia sumber. Silakan periksa kembali beberapa saat lagi.</p>
      <a class="btn btn-ghost" href="<?= e($m['url_detail']) ?>"><?= icon('arrow-left', 16) ?> Kembali ke detail</a>
    </section>
  <?php endif; ?>
</div>
