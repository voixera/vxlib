<?php component('icons'); ?>
<div class="auth-wrap">
  <div class="auth-card">
    <span style="color:var(--danger)">
      <svg width="54" height="54" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 3 22 20H2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
        <path d="M12 10v4.5M12 17.4v.2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
      </svg>
    </span>
    <h1 class="auth-title" style="font-size:26px">Sign-in didn’t complete</h1>
    <p class="auth-sub"><?= e($reason ?? 'Discord could not finish the handshake.') ?></p>
    <a class="btn btn-solid" href="/auth/discord.php" style="width:100%;justify-content:center">
      <?= icon('discord', 18) ?> Try again
    </a>
    <p class="auth-note"><a href="/">Keep browsing without an account →</a></p>
  </div>
</div>

