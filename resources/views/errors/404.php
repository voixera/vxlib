<?php component('states'); ?>
<div class="shell">
  <?php render_state('search', 'Rak ini tidak ada',
      $message ?? 'Halaman yang kamu cari tidak ada di rak manapun. Mungkin sudah dipindah, mungkin memang tidak pernah ada.',
      '/', 'Kembali ke beranda'); ?>
</div>
