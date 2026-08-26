<?php
/** Book detail. */
component('icons');
component('states');

$coverSrc = $book['cover_url'];
$coverTag = $coverSrc
    ? '<img src="' . e($coverSrc) . '" alt="Cover of ' . e($book['title']) . '" width="400" height="600">'
    : '<img src="/cover.php?' . http_build_query(['t' => $book['title'], 'a' => $book['author'], 'g' => $book['categories'][0]['name'] ?? '']) . '" alt="Generated cover for ' . e($book['title']) . '" width="400" height="600">';

$facts = [];
if ($book['publication_year']) $facts['Published'] = (string)$book['publication_year'];
if ($book['page_count']) $facts['Pages'] = number_format($book['page_count']);
$langNames = ['en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'fi' => 'Finnish'];
$facts['Language'] = $langNames[$book['language']] ?? strtoupper($book['language']);
if ($book['isbn']) $facts['ISBN-13'] = $book['isbn'];
$facts['Source'] = ($book['source'] ?? '') === 'voixlib' ? 'VoiXLib Original (SVG-illustrated)' : 'Project Gutenberg';
?>
<div class="shell">
  <div class="book-layout">
    <aside class="detail-cover-wrap reveal is-visible">
      <div class="detail-cover<?= empty($book['cover_url']) ? ' is-generated' : '' ?>"><?= $coverTag ?></div>
      <p class="detail-source-note">
        <?= icon('external', 14) ?>
        <?php if (($book['source'] ?? '') === 'voixlib'): ?>Original VoiXLib edition — illustrated in vector art
        <?php elseif ($book['readable']): ?>Readable copy from Project Gutenberg<?php else: ?>Metadata via Open Library — reading hosted off-site<?php endif; ?>
      </p>
    </aside>

    <div class="reveal is-visible">
      <?php if (!empty($book['categories'])): ?>
        <span class="detail-crumb"><?= e(implode(' · ', array_column($book['categories'], 'name'))) ?></span>
      <?php endif; ?>
      <h1 class="detail-title"><?= e($book['title']) ?></h1>
      <p class="detail-author">
        by <i><?= e($book['author']) ?></i><?php if ($book['author_life']): ?>
          <span> (<?= e($book['author_life']) ?>)</span><?php endif; ?>
      </p>

      <?php if (!empty($progress) && $user): ?>
        <div class="resume-banner">
          <?= icon('clock', 18) ?>
          <span>You’re <strong><?= (int)$progress['progress'] ?>%</strong> through</span>
          <span class="bar" role="presentation"><i style="--p:<?= (int)$progress['progress'] ?>%"></i></span>
        </div>
      <?php endif; ?>

      <?php if ($book['description']): ?>
        <p class="detail-desc"><?= e($book['description']) ?></p>
      <?php else: ?>
        <p class="detail-desc" style="font-style:italic;color:var(--ink-2)">
          No summary was provided by the source catalog — open the book and let it introduce itself.</p>
      <?php endif; ?>

      <?php if (!empty($book['subjects'])): ?>
        <div class="subject-tags">
          <?php foreach (array_slice(array_map('trim', explode(',', (string)$book['subjects'])), 0, 6) as $subject):
              if ($subject === '') continue; ?>
            <a class="chip" href="/search.php?q=<?= e(rawurlencode($subject)) ?>"><?= e(mb_substr($subject, 0, 40)) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <dl class="detail-facts">
        <?php foreach ($facts as $k => $v): ?>
          <div class="fact"><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div>
        <?php endforeach; ?>
      </dl>

      <div class="detail-actions">
        <?php if ($book['readable']): ?>
          <a class="btn btn-accent" href="/reader.php?id=<?= e($book['external_id']) ?>" id="read-now">
            <?= icon('book-open', 18) ?> Read now
          </a>
        <?php else: ?>
          <a class="btn btn-solid" href="<?= e($book['read_url'] ?: $book['source_url']) ?>" target="_blank" rel="noopener">
            <?= icon('external', 17) ?> Read at the source
          </a>
        <?php endif; ?>

        <?php $u = Auth::user(); ?>
        <button type="button" class="btn btn-ghost action-library" data-book-id="<?= $book['id'] ?>"
                data-status="<?= e($shelfStatus ?? '') ?>" <?= $u ? '' : 'data-needs-auth="1"' ?>>
          <?= icon('library', 17) ?>
          <span class="al-label"><?= !empty($shelfStatus) ? 'On your shelf' : 'Add to library' ?></span>
        </button>

        <button type="button" class="icon-btn action-bookmark<?= !empty($hasBookmark) ? ' is-saved' : '' ?>"
                data-book-id="<?= $book['id'] ?>" aria-pressed="<?= !empty($hasBookmark) ? 'true' : 'false' ?>"
                aria-label="Bookmark this book" <?= $u ? '' : 'data-needs-auth="1"' ?>>
          <?= icon(!empty($hasBookmark) ? 'bookmark-filled' : 'bookmark', 19) ?>
        </button>

        <button type="button" class="icon-btn action-share"
                data-title="<?= e($book['title']) ?>" data-url="<?= e(url('/book.php?id=' . $book['external_id'])) ?>"
                aria-label="Share this book">
          <?= icon('share', 18) ?>
        </button>
      </div>

      <?php if (!$book['readable']): ?>
        <p style="margin-top:16px;font-size:13.5px;color:var(--ink-2)">
          This edition isn’t available for in-browser reading yet — the button above takes you to its legitimate source.</p>
      <?php endif; ?>

      <?php if (!$user): ?>
        <p style="margin-top:22px;font-size:13.5px;color:var(--ink-2)">
          <a href="/auth/discord.php?next=<?= e(rawurlencode('/book.php?id=' . $book['external_id'])) ?>">Sign in with Discord</a>
          to keep this on your shelf across devices.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

