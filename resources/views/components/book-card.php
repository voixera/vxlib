<?php
/** Kartu media — dipakai shelf dan grid. $book dari MediaNormalizer atau baris lokal.
 *  $variant: '' | 'featured' | 'compact' */
component('icons');
$variant = $variant ?? '';
$coverSrc = $book['cover_url'] ?? null;
$fallbackQuery = http_build_query([
    't' => $book['title'],
    'a' => $book['author'] ?? '',
    'g' => $book['type_label'] ?? ($book['media_type'] ?? ''),
]);
$coverImg = $coverSrc
    ? '<img src="' . e($coverSrc) . '" alt="" loading="lazy" decoding="async" width="400" height="600" '
        . 'onerror="this.remove();this.parentElement.classList.add(\'cover-fallback\')">'
    : '<img src="/cover.php?' . $fallbackQuery . '" alt="Sampul ' . e($book['title']) . '" loading="lazy" decoding="async" width="400" height="600">';
$href = $book['url_detail'] ?? ('/detail/' . e($book['media_type'] ?? 'manga') . '/' . preg_replace('/^anilist:/', '', (string)($book['external_id'] ?? '')));
$year = $book['publication_year'] ?? ($book['year'] ?? null);
$typeLabel = $book['type_label'] ?? (($book['categories'][0]['name'] ?? false) ?: null);
?>
<article class="book-card<?= $variant ? ' ' . e($variant) : '' ?>">
  <a class="book-card-link" href="<?= e($href) ?>" aria-label="<?= e($book['title']) ?>">
    <div class="cover<?= empty($coverSrc) ? ' is-generated' : '' ?>">
      <?= $coverImg ?>
      <span class="cover-spine" aria-hidden="true"></span>
      <?php if (!empty($progressPct)): ?>
        <span class="cover-progress" style="--p:<?= (int)$progressPct ?>%" aria-hidden="true"></span>
      <?php endif; ?>
      <span class="cover-flag"><?= e($typeLabel ?? 'Media') ?></span>
      <?php if (!empty($book['score'])): ?><span class="cover-score"><?= (int)$book['score'] ?></span><?php endif; ?>
    </div>
    <div class="book-meta">
      <h3 class="book-title"><?= e($book['title']) ?></h3>
      <p class="book-author"><?= e($book['author'] ?? '') ?></p>
      <p class="book-sub">
        <?php if (!empty($progressPct)): ?><span class="pct"><?= (int)$progressPct ?>%</span> · <?php endif; ?>
        <?= $typeLabel !== null ? '<span>' . e($typeLabel) . '</span>' : '' ?>
        <?= $year ? '<span>' . (int)$year . '</span>' : '' ?>
      </p>
    </div>
  </a>
</article>
