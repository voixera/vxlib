<?php
/** My library. */
component('states');
component('icons');

$statusLabels = ['reading' => 'Currently reading', 'want_to_read' => 'Want to read', 'completed' => 'Completed'];
?>
<div class="shell">
  <header class="page-head">
    <span class="section-num">Your shelves</span>
    <h1>My library</h1>
    <p class="lede">Everything you save lives here — on the server, not just in this browser.</p>
  </header>

  <?php if ($continueReading): ?>
    <div style="margin:30px 0 10px">
      <?php view('components/shelf', [
          'heading' => 'Pick up where you left off',
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
        Bookmarks<span class="count"><?= count($bookmarks) ?></span>
      </button>
    </div>

    <?php foreach ($statusLabels as $key => $label): ?>
      <div class="library-panel<?= $key === 'reading' ? ' is-active' : '' ?>" id="panel-<?= $key ?>"
           role="tabpanel" aria-labelledby="tab-<?= $key ?>">
        <?php if (!($shelves[$key])): ?>
          <?php render_state('empty', ucfirst($label) . ' is empty',
              $key === 'want_to_read' ? 'Save books from any detail page and they’ll wait for you here.'
                  : ($key === 'completed' ? 'Finish a book and it will be archived on this shelf.'
                      : 'Start reading anything and it appears here automatically.'), '/explore.php', 'Find something to read'); ?>
        <?php else: ?>
          <div>
            <?php foreach ($shelves[$key] as $entry): $b = $entry['book']; ?>
              <div class="lib-row" data-lib-row data-book-id="<?= $b['id'] ?>">
                <a href="/book.php?id=<?= e($b['external_id']) ?>" class="cover"><img src="<?= e($b['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $b['title'], 'a' => $b['author']]))) ?>" alt="" loading="lazy"></a>
                <div>
                  <a class="row-title" style="text-decoration:none" href="/book.php?id=<?= e($b['external_id']) ?>"><?= e($b['title']) ?></a>
                  <p class="row-author"><?= e($b['author']) ?></p>
                  <?php if ($b['readable']): ?>
                    <a class="shelf-more" style="margin-top:6px" href="/reader.php?id=<?= e($b['external_id']) ?>">Continue <?= icon('arrow-right', 14) ?></a>
                  <?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;align-items:center">
                  <form method="post" action="/api/library.php?redirect=1" class="inline-status-form">
                    <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                    <select name="status" class="status-select" data-status-for="<?= $b['id'] ?>" aria-label="Change shelf for <?= e($b['title']) ?>">
                      <?php foreach (['reading' => 'Reading', 'want_to_read' => 'Want to read', 'completed' => 'Completed'] as $sv => $sl): ?>
                        <option value="<?= $sv ?>" <?= $entry['status'] === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                  <button type="button" class="remove-btn" data-remove-from-library="<?= $b['id'] ?>" aria-label="Remove <?= e($b['title']) ?> from library">
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
        <?php render_state('empty', 'No bookmarks yet', 'While reading, tap the ribbon button to pin your exact place. Bookmarked spots collect here.'); ?>
      <?php else: ?>
        <?php foreach ($bookmarks as $bm): $bk = $bm['books'] ?? null; if (!$bk) continue; ?>
          <div class="bookmark-row" data-bookmark-id="<?= $bm['id'] ?>">
            <a href="/book.php?id=<?= e($bk['external_id']) ?>" class="cover"><img src="<?= e($bk['cover_url'] ?? '/cover.php?t=' . rawurlencode((string)$bk['title'])) ?>" alt="" loading="lazy"></a>
            <div>
              <p class="row-title" style="font-size:16px"><?= e($bk['title']) ?></p>
              <p style="font-size:13px;color:var(--ink-2);margin-top:2px">
                <?= icon('bookmark', 13) ?> <?= e(mb_substr((string)($bm['location'] ?? ''), 0, 80)) ?>
                · <?= e(date('M j', strtotime((string)$bm['created_at']))) ?></p>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <a class="btn btn-ghost" style="padding:8px 14px;font-size:13px"
                 href="/reader.php?id=<?= e($bk['external_id']) ?>&bm=<?= e(rawurlencode((string)$bm['location'])) ?>">Jump</a>
              <button type="button" class="remove-btn" data-remove-bookmark="<?= $bm['id'] ?>" aria-label="Delete bookmark">
                <?= icon('trash', 17) ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>


