<?php
/** Horizontal editorial shelf. Expects $heading, $books, optional $progress map, $href ("view all"). */
$progressMap = $progress ?? [];
?>
<section class="shelf-block reveal">
  <header class="shelf-head">
    <h2 class="shelf-title"><?= e($heading) ?></h2>
    <?php if (!empty($href)): ?>
      <a class="shelf-more" href="<?= e($href) ?>">View all <?= icon('arrow-right', 16) ?></a>
    <?php endif; ?>
  </header>
  <div class="shelf" role="list" tabindex="-1">
    <?php foreach ($books as $b): ?>
      <div class="shelf-item" role="listitem">
        <?php view('components/book-card', ['book' => $b, 'progressPct' => $progressMap[$b['id']] ?? null]); ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
