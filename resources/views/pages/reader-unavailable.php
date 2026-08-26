<?php
/** Reader fallback: catalog book with no in-browser copy. */
component('states');
?>
<div class="shell">
  <div class="page-head">
    <span class="section-num">Reader</span>
    <h1><?= e($book['title']) ?></h1>
    <p class="lede">by <?= e($book['author']) ?></p>
  </div>
  <?php render_state(
      'empty',
      'This one stays at the source',
      'VoiXLib only hosts reading copies it can verify and re-typeset. This title isn’t digitized for in-browser reading yet — its legitimate source has it though.',
      $book['read_url'] ?: $book['source_url'],
      'Open at the source'
  ); ?>
</div>

