<?php component('brand'); component('icons'); ?>
<div class="auth-wrap">
  <div class="auth-card reveal is-visible">
    <span class="auth-mark"><?= voixlib_mark(52) ?></span>
    <h1 class="auth-title">Buka perpustakaanmu</h1>
    <p class="auth-sub"><?= $required
        ? 'Halaman itu bagian dari perpustakaan pribadimu — masuk untuk melanjutkan.'
        : 'Masuk untuk menyimpan rak, bookmark, dan progres membaca di semua perangkat.' ?></p>

    <a class="btn btn-discord" href="/auth/discord.php<?= $next !== '/' ? '?next=' . e(rawurlencode($next)) : '' ?>"
       style="width:100%;justify-content:center;padding:14px;font-size:16px">
      <?= icon('discord', 20) ?> Masuk dengan Discord
    </a>

    <p class="auth-note">Kami hanya membaca ID, nama, dan avatar Discord-mu — tidak pernah kata sandinya.
      Tidak ada akun yang dibuat di sisi Discord.</p>
  </div>
</div>

