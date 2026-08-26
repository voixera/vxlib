<?php
/** Reader — chromeless layout, content fetched via /api/content.php. */
component('icons');
component('states');
$gid = $book['gutenberg_id'] !== null ? (int)$book['gutenberg_id'] : 0;
?>
<div class="reader-progress-rail" aria-hidden="true"><i id="rp-bar"></i></div>

<div class="reader-shell" data-reader-root data-book-id="<?= $book['id'] ?>"
     data-gutenberg-id="<?= $gid ?>" data-title="<?= e($book['title']) ?>"
     data-format="<?= e(MangaService::isManga($book) ? 'manga' : 'text') ?>">
  <div class="reader-topbar">
    <a class="icon-btn" href="/book.php?id=<?= e($book['external_id']) ?>" aria-label="Back to book details">
      <?= icon('arrow-left', 18) ?>
    </a>
    <span class="rt-title"><?= e($book['title']) ?> <span style="color:var(--ink-2)">· <?= e($book['author']) ?></span></span>

    <button type="button" class="icon-btn" data-reader-toc aria-label="Table of contents"><?= icon('toc', 19) ?></button>
    <button type="button" class="icon-btn" id="rs-open" aria-label="Reader settings" aria-expanded="false"><?= icon('type', 19) ?></button>
  </div>

  <div class="reader-body" id="reader-body">
    <div id="reader-skeleton" aria-hidden="true">
      <?php for ($i = 0; $i < 14; $i++): ?>
        <div class="skeleton skel-reader-p<?= $i % 4 === 3 ? ' short' : '' ?>" style="width:<?= 100 - ($i % 3) * 6 ?>%"></div>
      <?php endfor; ?>
    </div>
    <div id="reader-error" hidden style="padding-top:40px">
      <?php render_state('offline', 'This copy won’t open',
          'The reading service could not fetch the text just now. You can still read it at its original home.',
          null); ?>
      <div style="text-align:center">
        <a class="btn btn-ghost" href="<?= e($book['read_url']) ?>" target="_blank" rel="noopener">
          <?= icon('external', 16) ?> Open at Project Gutenberg</a>
        <a class="btn btn-solid" href="/book.php?id=<?= e($book['external_id']) ?>" style="margin-left:10px">Back to the book</a>
      </div>
    </div>
    <div id="reader-content" hidden>
      <h2 class="reader-chapter-title" id="chapter-heading"></h2>
      <div class="reader-content" id="chapter-content"></div>
      <nav class="reader-chapter-nav" aria-label="Chapters">
        <button type="button" class="btn btn-ghost" id="prev-chapter"><?= icon('arrow-left', 16) ?> Previous</button>
        <button type="button" class="btn btn-solid" id="next-chapter">Next <?= icon('arrow-right', 16) ?></button>
      </nav>
    </div>

    <!-- flipbook (manga / manhwa) -->
    <div id="flipbook" hidden aria-label="Illustrated pages">
      <div class="fb-stage" id="fb-stage">
        <div class="fb-book" id="fb-book"></div>
        <div class="fb-center" aria-hidden="true"></div>
        <button type="button" class="fb-corner fb-prev" id="fb-prev" aria-label="Previous page"><?= icon('arrow-left', 20) ?></button>
        <button type="button" class="fb-corner fb-next" id="fb-next" aria-label="Next page"><?= icon('arrow-right', 20) ?></button>
      </div>
      <div class="fb-status">
        <span id="fb-chapter"></span>
        <span id="fb-page-no"></span>
      </div>
    </div>
  </div>

  <!-- settings drawer -->
  <aside class="reader-settings-panel" id="reader-panel" aria-label="Reader settings" role="dialog" aria-modal="true" hidden>
    <button type="button" class="icon-btn rs-close" id="rs-close" aria-label="Close settings"><?= icon('close', 18) ?></button>

    <h3 style="font-size:17px;margin-bottom:4px">Reading comfort</h3>
    <p class="setting-help">Changes apply instantly and are saved to this device<?php if (!empty($user)): ?> and your account<?php endif; ?>.</p>

    <div id="text-settings">
      <div class="setting-group" style="border:none;padding-top:8px">
        <h2>Text size</h2>
        <div class="range-row">
          <input type="range" id="rf-font" min="14" max="24" step="1" value="18" aria-label="Font size in pixels">
          <output id="rf-font-out">18px</output>
        </div>
      </div>
      <div class="setting-group">
        <h2>Line height</h2>
        <div class="range-row">
          <input type="range" id="rf-leading" min="1.4" max="2.2" step="0.1" value="1.7" aria-label="Line height">
          <output id="rf-leading-out">1.7</output>
        </div>
      </div>
      <div class="setting-group">
        <h2>Page width</h2>
        <div class="range-row">
          <input type="range" id="rf-width" min="34" max="60" step="1" value="42" aria-label="Page width in rem">
          <output id="rf-width-out">42rem</output>
        </div>
      </div>
    </div>
    <div class="setting-group">
      <h2>Theme</h2>
      <div class="seg" role="radiogroup" aria-label="Reader theme">
        <label><input type="radio" name="rtheme" value="light"><span>Paper</span></label>
        <label><input type="radio" name="rtheme" value="sepia"><span>Sepia</span></label>
        <label><input type="radio" name="rtheme" value="dark"><span>Dark</span></label>
      </div>
    </div>

    <?php if (empty($user)): ?>
      <p class="setting-help" style="margin-top:20px;border-top:1px solid var(--line);padding-top:16px">
        Reading anonymously — progress lives on this device.
        <a href="/auth/discord.php?next=<?= e(rawurlencode('/reader.php?id=' . $book['external_id'])) ?>">Sign in</a> to sync it.</p>
    <?php endif; ?>
  </aside>

  <!-- floating actions -->
  <div class="reader-fab-col">
    <button type="button" class="reader-fab" id="fab-bookmark"
            aria-label="Bookmark this position" title="Bookmark this position"><?= icon('bookmark', 20) ?></button>
    <button type="button" class="reader-fab" id="fab-library"
            aria-label="Save to library" title="Save to library"><?= icon('library', 20) ?></button>
  </div>
</div>

<!-- TOC drawer -->
<div class="reader-settings-panel" id="toc-panel" aria-label="Table of contents" role="dialog" aria-modal="true" hidden>
  <button type="button" class="icon-btn rs-close" id="toc-close" aria-label="Close contents"><?= icon('close', 18) ?></button>
  <h3 style="font-size:17px;margin-bottom:14px">Contents</h3>
  <ul class="reader-toc-list" id="toc-list"></ul>
</div>

<script>window.VOIXLIB_READER = { externalId: <?= json_encode($book['external_id']) ?> };</script>

