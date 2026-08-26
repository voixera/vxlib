<?php
/** Profile. */
component('states');
component('icons');
?>
<div class="shell">
  <div class="profile-head reveal is-visible">
    <?php if (!empty($user['avatar_url'])): ?>
      <img class="profile-avatar" src="<?= e($user['avatar_url']) ?>" alt="Discord avatar" referrerpolicy="no-referrer">
    <?php else: ?>
      <span class="profile-avatar avatar-fallback" style="font-size:38px"><?= e(mb_strtoupper(mb_substr((string)$user['display_name'], 0, 1))) ?></span>
    <?php endif; ?>
    <div>
      <h1 class="profile-name"><?= e($user['display_name'] ?: $user['username']) ?></h1>
      <p class="profile-handle">@<?= e($user['username']) ?> · via Discord</p>
      <p class="profile-joined"><?= icon('clock', 15) ?> Reading here since
        <?= e(date('F Y', strtotime((string)$user['created_at']))) ?></p>
    </div>
  </div>

  <div class="stat-band reveal is-visible">
    <div class="stat-cell"><b><?= number_format((int)$stats['saved']) ?></b><span style="color:var(--ink-2);font-size:13px">Books saved</span></div>
    <div class="stat-cell"><b><?= number_format((int)$stats['reading']) ?></b><span style="color:var(--ink-2);font-size:13px">Currently reading</span></div>
    <div class="stat-cell"><b><?= number_format((int)$stats['completed']) ?></b><span style="color:var(--ink-2);font-size:13px">Completed</span></div>
    <div class="stat-cell"><b><?= number_format((int)$stats['bookmarks']) ?></b><span style="color:var(--ink-2);font-size:13px">Bookmarks</span></div>
  </div>

  <section class="shelf-block">
    <header class="shelf-head"><h2 class="shelf-title">Recently opened</h2></header>
    <?php if (!$history): ?>
      <?php render_state('empty', 'No reading history yet',
          'Open any book in the reader and it will show up here.', '/explore.php', 'Find a book'); ?>
    <?php else: ?>
      <div class="catalog-grid">
        <?php foreach ($history as $h): view('components/book-card', ['book' => $h['book']]); endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div style="padding-bottom:70px;display:flex;gap:12px">
    <a class="btn btn-ghost" href="/settings.php"><?= icon('settings', 16) ?> Settings</a>
    <a class="btn btn-ghost" href="/logout.php">Sign out</a>
  </div>
</div>


