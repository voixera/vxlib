<?php
/** Book card — used by shelves and grids everywhere. Expects $book, optional $progressPct. */
$coverSrc = $book['cover_url'] ?? null;
if ($coverSrc) {
    $coverImg = '<img src="' . e($coverSrc) . '" alt="" loading="lazy" decoding="async" width="400" height="600" '
        . 'onerror="this.remove();this.parentElement.classList.add(\'cover-fallback\')">';
} else {
    $genre = $book['categories'][0]['name'] ?? '';
    $coverImg = '<img src="/cover.php?' . http_build_query(['t' => $book['title'], 'a' => $book['author'], 'g' => $genre])
        . '" alt="Generated cover for ' . e($book['title']) . '" loading="lazy" decoding="async" width="400" height="600">';
}
$cat = $book['categories'][0]['name'] ?? null;
?>
<article class="book-card">
  <a class="book-card-link" href="/book.php?id=<?= e($book['external_id']) ?>" aria-label="<?= e($book['title']) ?> by <?= e($book['author']) ?>">
    <div class="cover<?= empty($coverSrc) ? ' is-generated' : '' ?><?= empty($coverSrc) ? ' cover-fallback' : '' ?>">
      <?= $coverImg ?>
      <span class="cover-spine" aria-hidden="true"></span>
      <?php if (!empty($progressPct)): ?>
        <span class="cover-progress" style="--p:<?= (int)$progressPct ?>%" aria-hidden="true"></span>
      <?php endif; ?>
      <?php if (!$book['readable']): ?>
        <span class="cover-flag">Source only</span>
      <?php endif; ?>
    </div>
    <div class="book-meta">
      <h3 class="book-title"><?= e($book['title']) ?></h3>
      <p class="book-author"><?= e($book['author']) ?></p>
      <p class="book-sub">
        <?php if (!empty($progressPct)): ?><span class="pct"><?= (int)$progressPct ?>%</span> · <?php endif; ?>
        <?= $cat !== null ? '<span>' . e($cat) . '</span>' : '' ?>
        <?= $book['publication_year'] ? '<span>' . (int)$book['publication_year'] . '</span>' : '' ?>
      </p>
    </div>
  </a>
</article>
