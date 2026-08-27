<?php
/** My library. */
component('states');
component('icons');

$statusLabels = ['reading' => 'Sedang Dibaca', 'want_to_read' => 'Ingin Dibaca', 'completed' => 'Selesai'];
?>
<div class="shell">
  <header class="page-head">
    <span class="section-num">Rak pribadi</span>
    <h1>Perpustakaan Saya</h1>
    <p class="lede">Semua yang kamu simpan tersimpan di server — bukan cuma di browser ini.</p>
  </header>

  <?php if ($continueReading): ?>
    <div style="margin:30px 0 10px">
      <?php view('components/shelf', [
          'heading' => 'Lanjutkan dari terakhir kali',
          'books'   => $continueReading,
          'progress'=> array_reduce($continueReading, function ($m, $b) { $m[$b['id']] = $b['progress_pct']; return $m; }, []),
      ]); ?>
    </div>
    <hr class="section-rule">
  <?php endif; ?>

  <div style="padding-top:26px">
    <div class="shelves-tabs" role="tablist" aria-label="Library shelves">
      <?php foreach ($statusLabels as $key => $label): $n = count($shelves[$key] ?? []); ?>
        <button type="button" class="shelf-tab" role="tab" id="tab-<?= $key ?>"
                aria-selected="<?= $key === 'reading' ? 'true' : 'false' ?>"
                aria-controls="panel-<?= $key ?>" data-shelf-tab="<?= $key ?>">
          <?= e($label) ?><span class="count"><?= $n ?></span>
        </button>
      <?php endforeach; ?>
      <button type="button" class="shelf-tab" role="tab" id="tab-bookmarks"
              aria-selected="false" aria-controls="panel-bookmarks" data-shelf-tab="bookmarks">
        Bookmark<span class="count"><?= count($bookmarks) ?></span>
      </button>
    </div>

    <?php foreach ($statusLabels as $key => $label): ?>
      <div class="library-panel<?= $key === 'reading' ? ' is-active' : '' ?>" id="panel-<?= $key ?>"
           role="tabpanel" aria-labelledby="tab-<?= $key ?>">
        <?php if (!($shelves[$key])): ?>
          <?php render_state('empty', e(ucfirst($label)) . ' masih kosong',
              $key === 'want_to_read' ? 'Simpan judul dari halaman detail mana pun dan mereka menunggumu di sini.'
                  : ($key === 'completed' ? 'Selesaikan satu judul dan ia terarsip di rak ini.'
                      : 'Mulai membuka judul apa pun dan ia muncul otomatis di sini.'), '/explore.php', 'Cari judul'); ?>
        <?php else: ?>
          <div>
            <?php foreach ($shelves[$key] as $entry): $b = $entry['book']; ?>
              <div class="lib-row" data-lib-row data-book-id="<?= $b['id'] ?>">
                <a href="/detail/<?= e($b['media_type'] ?? 'manga') ?>/<?= e(preg_replace('/^anilist:/', '', (string)$b['external_id'])) ?>" class="cover"><img src="<?= e($b['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $b['title'], 'a' => $b['author']]))) ?>" alt="" loading="lazy"></a>
                <div>
                  <a class="row-title" style="text-decoration:none" href="/detail/<?= e($b['media_type'] ?? 'manga') ?>/<?= e(preg_replace('/^anilist:/', '', (string)$b['external_id'])) ?>"><?= e($b['title']) ?></a>
                  <p class="row-author"><?= e($b['author']) ?></p>
                </div>
                <div style="display:flex;gap:10px;align-items:center">
                  <form method="post" action="/api/library.php?redirect=1" class="inline-status-form">
                    <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                    <select name="status" class="status-select" data-status-for="<?= $b['id'] ?>" aria-label="Pindah rak untuk <?= e($b['title']) ?>">
                      <?php foreach (['reading' => 'Sedang Dibaca', 'want_to_read' => 'Ingin Dibaca', 'completed' => 'Selesai'] as $sv => $sl): ?>
                        <option value="<?= $sv ?>" <?= $entry['status'] === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                  <button type="button" class="remove-btn" data-remove-from-library="<?= $b['id'] ?>" aria-label="Hapus <?= e($b['title']) ?> dari perpustakaan">
                    <?= icon('trash', 18) ?>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="library-panel" id="panel-bookmarks" role="tabpanel" aria-labelledby="tab-bookmarks">
      <?php if (!$bookmarks): ?>
        <?php render_state('empty', 'Belum ada bookmark', 'Saat membuka halaman detail, ketuk ikon pita untuk menandai judul. Semua tanda itu berkumpul di sini.'); ?>
      <?php else: ?>
        <?php foreach ($bookmarks as $bm): $bk = $bm['books'] ?? null; if (!$bk) continue; ?>
          <div class="bookmark-row" data-bookmark-id="<?= $bm['id'] ?>">
            <a href="/detail/<?= e($bk['media_type'] ?? 'manga') ?>/<?= e(preg_replace('/^anilist:/', '', (string)$bk['external_id'])) ?>" class="cover"><img src="<?= e($bk['cover_url'] ?? '/cover.php?t=' . rawurlencode((string)$bk['title'])) ?>" alt="" loading="lazy"></a>
            <div>
              <p class="row-title" style="font-size:16px"><?= e($bk['title']) ?></p>
              <p style="font-size:13px;color:var(--ink-2);margin-top:2px">
                <?= icon('bookmark', 13) ?> Ditandai
                · <?= e(date('j M Y', strtotime((string)$bm['created_at']))) ?></p>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <a class="btn btn-ghost btn-sm"
                  href="/detail/<?= e($bk['media_type'] ?? 'manga') ?>/<?= e(preg_replace('/^anilist:/', '', (string)$bk['external_id'])) ?>">Buka</a>
              <button type="button" class="remove-btn" data-remove-bookmark="<?= $bm['id'] ?>" aria-label="Hapus bookmark">
                <?= icon('trash', 17) ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>


