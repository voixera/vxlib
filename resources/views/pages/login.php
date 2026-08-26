<?php component('icons'); ?>
<div class="auth-wrap">
  <div class="auth-card reveal is-visible">
    <span class="auth-mark">
      <svg width="52" height="52" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="1.25" y="1.25" width="29.5" height="29.5" rx="3" stroke="currentColor" stroke-width="1.6"/>
        <path d="M8 7.5 L15.2 16 L8 24.5 L11 24.5 L16.1 18.4 L21.2 24.5 L24.2 24.5 L17 16 L24.2 7.5 L21.2 7.5 L16.1 13.6 L11 7.5 Z" fill="currentColor" opacity=".92"/>
        <path d="M11 7.5h-3L15.2 16l1.9-2.3z" fill="var(--accent)"/>
      </svg>
    </span>
    <h1 class="auth-title">Open the library</h1>
    <p class="auth-sub"><?= $required
        ? 'That page is part of your personal library — sign in to continue.'
        : 'Sign in to keep shelves, bookmarks and reading progress synced across devices.' ?></p>

    <a class="btn btn-discord" href="/auth/discord.php<?= $next !== '/' ? '?next=' . e(rawurlencode($next)) : '' ?>"
       style="width:100%;justify-content:center;padding:14px;font-size:16px">
      <?= icon('discord', 20) ?> Continue with Discord
    </a>

    <p class="auth-note">We only read your Discord ID, name and avatar — never your password.
      No account is created on Discord’s side.</p>
  </div>
</div>

