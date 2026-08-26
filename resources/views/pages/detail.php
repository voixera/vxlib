<?php
/** Detail judul: metadata nyata dari AniList. */
component('icons');
component('states');

$typeLabels = ['anime' => 'Anime', 'manga' => 'Manga', 'manhwa' => 'Manhwa'];
$u = Auth::user();
?>
<div class="detail-banner" style="<?= !empty($m['banner_url']) ? '--banner:url(' . e($m['banner_url']) . ')' : '' ?>" aria-hidden="true"></div>

<div class="shell">
  <div class="book-layout detail-media">
    <aside class="detail-cover-wrap reveal is-visible">
      <div class="detail-cover<?= empty($m['cover_url']) ? ' is-generated' : '' ?>">
        <img src="<?= e($m['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $m['title'], 'a' => $m['author'], 'g' => $m['type_label']]))) ?>"
             alt="Sampul <?= e($m['title']) ?>" width="400" height="600">
      </div>
      <?php if (!empty($m['alt_title'])): ?>
        <p class="detail-source-note"><?= e($m['alt_title']) ?></p>
      <?php endif; ?>
    </aside>

    <div class="reveal is-visible">
      <span class="detail-crumb">
        <a href="/<?= e($m['media_type']) ?>"><?= e($typeLabels[$m['media_type']]) ?></a>
        <?php if ($m['status_label']): ?> · <?= e($m['status_label']) ?><?php endif; ?>
        <?php if ($m['year']): ?> · <?= (int)$m['year'] ?><?php endif; ?>
      </span>
      <h1 class="detail-title"><?= e($m['title']) ?></h1>
      <p class="detail-author">by <i><?= e($m['author']) ?></i><?php if (!empty($m['artist'])): ?>
        <span> · Art: <?= e($m['artist']) ?></span><?php endif; ?></p>

      <?php if (!empty($progress) && $user): ?>
        <div class="resume-banner">
          <?= icon('clock', 18) ?>
          <span>Terakhir dibuka · progress <strong><?= (int)$progress['progress'] ?>%</strong></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($m['genres'])): ?>
        <div class="subject-tags">
          <?php foreach ($m['genres'] as $g): ?>
            <a class="chip" href="/explore.php?genre=<?= e(rawurlencode($g)) ?>&type=<?= e($m['media_type']) ?>"><?= e($g) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <dl class="detail-facts">
        <?php if ($m['format_label']): ?><div class="fact"><dt>Format</dt><dd><?= e($m['format_label']) ?></dd></div><?php endif; ?>
        <?php if ($m['episodes'] !== null): ?><div class="fact"><dt>Episode</dt><dd><?= (int)$m['episodes'] ?></dd></div><?php endif; ?>
        <?php if ($m['chapters'] !== null): ?><div class="fact"><dt>Chapter</dt><dd><?= (int)$m['chapters'] ?></dd></div><?php endif; ?>
        <?php if ($m['volumes'] !== null): ?><div class="fact"><dt>Volume</dt><dd><?= (int)$m['volumes'] ?></dd></div><?php endif; ?>
        <?php if ($m['score'] !== null): ?><div class="fact"><dt>Rating</dt><dd><?= number_format($m['score'] / 10, 1) ?> / 10</dd></div><?php endif; ?>
        <?php if ($m['popularity'] !== null): ?><div class="fact"><dt>Popularitas</dt><dd><?= number_format((int)$m['popularity']) ?> pengguna</dd></div><?php endif; ?>
        <div class="fact"><dt>Status</dt><dd><?= e($m['status_label'] ?? 'Tidak tersedia') ?></dd></div>
        <div class="fact"><dt>Tahun</dt><dd><?= $m['year'] ? (int)$m['year'] : 'Tidak tersedia' ?></dd></div>
      </dl>

      <div class="detail-actions">
        <a class="btn btn-accent" href="/read/<?= e($m['media_type']) ?>/<?= (int)preg_replace('/^anilist:/', '', (string)$m['external_id']) ?>">
          <?= icon($m['media_type'] === 'anime' ? 'play' : 'book-open', 18) ?> <?= $m['media_type'] === 'anime' ? 'Tonton di VoiXLib' : 'Baca di VoiXLib' ?>
        </a>

        <button type="button" class="btn btn-ghost action-library" data-book-id="<?= $bookId ?? '' ?>"
                data-status="<?= e($shelfStatus ?? '') ?>" <?= ($u && $bookId) ? '' : 'data-needs-auth="1"' ?>>
          <?= icon('library', 17) ?>
          <span class="al-label"><?= !empty($shelfStatus) ? 'Di rak kamu' : 'Tambahkan ke Perpustakaan' ?></span>
        </button>

        <button type="button" class="icon-btn action-bookmark<?= !empty($hasBookmark) ? ' is-saved' : '' ?>"
                data-book-id="<?= $bookId ?? '' ?>" aria-pressed="<?= !empty($hasBookmark) ? 'true' : 'false' ?>"
                aria-label="Bookmark judul ini" <?= ($u && $bookId) ? '' : 'data-needs-auth="1"' ?>>
          <?= icon(!empty($hasBookmark) ? 'bookmark-filled' : 'bookmark', 19) ?>
        </button>

        <button type="button" class="icon-btn action-share"
                data-title="<?= e($m['title']) ?>" data-url="<?= e(url($m['url_detail'])) ?>"
                aria-label="Bagikan judul ini">
          <?= icon('share', 18) ?>
        </button>
      </div>

      <?php if (!$migrationOk && SupabaseClient::configured()): ?>
        <p style="margin-top:12px;font-size:13px;color:var(--danger)">
          Penyimpanan perpustakaan belum aktif untuk judul ini (jalankan supabase/migration-002-media.sql).</p>
      <?php elseif (!$u): ?>
        <p style="margin-top:14px;font-size:13.5px;color:var(--ink-2)">
          <a href="/auth/discord.php?next=<?= e(rawurlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>">Masuk dengan Discord</a>
          untuk menyimpan judul ini ke rak pribadimu.</p>
      <?php endif; ?>

      <?php if (!empty($m['description'])): ?>
        <section class="detail-synopsis">
          <h2>Sinopsis</h2>
          <p class="detail-desc"><?= nl2br(e($m['description'])) ?></p>
          <p class="synopsis-credit">Sinopsis &amp; data © AniList — diterjemahkan otomatis oleh penyedia bila tersedia.</p>
        </section>
      <?php else: ?>
        <section class="detail-synopsis">
          <h2>Sinopsis</h2>
          <p class="detail-desc" style="font-style:italic;color:var(--ink-2)">Tidak tersedia.</p>
        </section>
      <?php endif; ?>
    </div>
  </div>
</div>
