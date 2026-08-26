<?php
/** Homepage. */
component('states');
component('icons');

$featuredBooks = $featured['books'] ?? [];
$trendingBooks = array_slice($trending['books'] ?? [], 0, 12);
$recentBooks   = array_slice($recent['books'] ?? [], 0, 12);
$continue      = $continueReading ?? [];
$heroCovers    = array_slice($trendingBooks ?: $recentBooks, 0, 3);
?>

<!-- ── Hero ─────────────────────────────────────────────── -->
<section class="hero">
  <div class="shell">
    <div class="hero-grid">
      <div>
        <p class="hero-kicker">A digital library for slow readers</p>
        <h1 class="hero-title">Read beyond<br>the shelf<em>.</em></h1>
        <p class="hero-lede">Manga and manhwa with real page-turns, anime tie-ins, and novels in Bahasa
          Indonesia — all in one calm library. No feeds, no noise. Just you, the page, and your place in it.</p>

        <form class="hero-search" action="/search.php" method="get" role="search">
          <?= icon('search', 19) ?>
          <label class="visually-hidden" for="hero-q">Search the catalog</label>
          <input id="hero-q" type="search" name="q" placeholder="Try “manga”, “Sakura Circuit”, “novel indonesia”…" maxlength="120">
          <button class="btn btn-accent" type="submit">Search</button>
        </form>

        <?php if (!empty($stats)): ?>
          <div class="hero-stats" aria-label="Live catalog statistics">
            <div class="stat"><b><?= number_format((int)$stats['books']) ?></b><span><?= $stats['books'] === 1 ? 'book' : 'books' ?></span></div>
            <?php if ($stats['readers'] !== null): ?>
              <div class="stat"><b><?= number_format((int)$stats['readers']) ?></b><span><?= $stats['readers'] === 1 ? 'reader' : 'readers' ?></span></div>
            <?php endif; ?>
            <?php if ($stats['opens'] !== null): ?>
              <div class="stat"><b><?= number_format((int)$stats['opens']) ?></b><span><?= $stats['opens'] === 1 ? 'reading session' : 'reading sessions' ?></span></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="hero-stage">
        <?php view('components/hero-scene'); ?>
        <div class="hero-covers" data-parallax-covers>
          <?php foreach ($heroCovers as $i => $hb):
            if (empty($hb['cover_url'])) continue; ?>
            <a class="hero-cover" href="/book.php?id=<?= e($hb['external_id']) ?>" style="animation-delay:-<?= $i * 2 ?>s" tabindex="-1" aria-hidden="true">
              <img src="<?= e($hb['cover_url']) ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>" decoding="async">
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($continue): ?>
<section class="section" style="padding-bottom:0">
  <div class="shell">
    <?php view('components/shelf', [
        'heading' => 'Continue reading',
        'books'   => $continue,
        'progress'=> array_reduce($continue, function ($m, $b) { $m[$b['id']] = $b['progress_pct']; return $m; }, []),
    ]); ?>
  </div>
</section>
<?php endif; ?>

<div class="shell"><hr class="section-rule"></div>

<!-- ── Featured ─────────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <header class="section-head reveal">
      <div><span class="section-num">01 · Curated</span>
        <h2>This week’s featured</h2></div>
      <a class="shelf-more" href="/explore.php?sort=popular">Browse everything <?= icon('arrow-right', 16) ?></a>
    </header>

    <?php if ($featuredBooks): ?>
      <div class="editorial-grid reveal">
        <?php $heroFb = $featuredBooks[0]; ?>
        <article class="feature-oversized">
          <a class="feature-oversized-link" href="/book.php?id=<?= e($heroFb['external_id']) ?>">
            <div class="cover-stage">
              <img src="<?= e($heroFb['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $heroFb['title'], 'a' => $heroFb['author']]))) ?>"
                   alt="" loading="eager" decoding="async">
              <span class="cover-spine" aria-hidden="true"></span>
            </div>
            <div class="feature-oversized-content">
              <span class="editorial-badge">Featured selection</span>
              <h3 class="feature-title"><?= e($heroFb['title']) ?></h3>
              <p class="feature-author">By <?= e($heroFb['author']) ?></p>
              <?php if (!empty($heroFb['description'])): ?>
                <p class="feature-desc"><?= e(mb_substr($heroFb['description'], 0, 240)) ?>…</p>
              <?php endif; ?>
              <span class="feature-action">Read book <?= icon('arrow-right', 16) ?></span>
            </div>
          </a>
        </article>

        <div class="feature-asymmetric-list">
          <?php foreach (array_slice($featuredBooks, 1, 4) as $fb): ?>
            <article class="feature-horizontal-card">
              <a href="/book.php?id=<?= e($fb['external_id']) ?>" class="feature-horizontal-link">
                <img src="<?= e($fb['cover_url'] ?? ('/cover.php?' . http_build_query(['t' => $fb['title'], 'a' => $fb['author']]))) ?>"
                     alt="" loading="lazy" decoding="async">
                <div class="feature-horizontal-meta">
                  <h4><?= e($fb['title']) ?></h4>
                  <p><?= e($fb['author']) ?></p>
                  <?php if (!empty($fb['categories'][0]['name'])): ?>
                    <span class="tag"><?= e($fb['categories'][0]['name']) ?></span>
                  <?php endif; ?>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php elseif (($featured['error'] ?? null) === 'supabase_not_configured'): ?>
      <?php render_state('offline', 'The stacks are still being wired up',
          'This VoiXLib deployment has not been connected to its database yet. See README → Setup to finish installation.'); ?>
    <?php else: ?>
      <?php render_state('empty', 'No featured books yet', 'The curators haven’t pinned anything this week — explore the full catalog instead.', '/explore.php', 'Explore the catalog'); ?>
    <?php endif; ?>
  </div>
</section>

<div class="shell"><hr class="section-rule"></div>

<!-- ── Trending ─────────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <?php if ($trendingBooks): ?>
      <?php view('components/shelf', [
          'heading' => 'Most opened lately',
          'books'   => $trendingBooks,
          'href'    => '/explore.php?sort=popular',
      ]); ?>
    <?php else: ?>
      <?php render_state('offline', 'Catalog unavailable', 'We couldn’t reach the library database just now. It usually comes back on its own — try again in a moment.'); ?>
    <?php endif; ?>
  </div>
</section>

<div class="shell"><hr class="section-rule"></div>

<!-- ── Recently added ───────────────────────────────────── -->
<section class="section" style="padding-top:0">
  <div class="shell">
    <?php if ($recentBooks): ?>
      <?php view('components/shelf', [
          'heading' => 'Recently added',
          'books'   => $recentBooks,
          'href'    => '/explore.php?sort=newest',
      ]); ?>
    <?php endif; ?>
  </div>
</section>

<div class="shell"><hr class="section-rule"></div>

<!-- ── Categories ───────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <header class="section-head reveal">
      <div><span class="section-num">02 · Wander</span>
        <h2>Explore by mood</h2></div>
    </header>
    <div class="cat-grid reveal">
      <?php foreach (($categories ?? []) as $ci => $cat): ?>
        <a class="cat-tile" href="/explore.php?category=<?= e($cat['slug']) ?>">
          <svg class="cat-art" width="86" height="86" viewBox="0 0 86 86" aria-hidden="true"
               style="transform-origin:center">
            <?php
            $shapes = [
                '<path d="M14 62 L34 26 L54 62 Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="62" cy="24" r="9" fill="none" stroke="currentColor" stroke-width="2"/>',
                '<circle cx="43" cy="43" r="20" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="43" cy="43" r="10" fill="currentColor" opacity=".25"/>',
                '<rect x="22" y="22" width="42" height="42" rx="4" fill="none" stroke="currentColor" stroke-width="2" transform="rotate(8 43 43)"/>',
                '<path d="M18 58 Q 43 18 68 58" fill="none" stroke="currentColor" stroke-width="2"/><line x1="30" y1="66" x2="56" y2="66" stroke="currentColor" stroke-width="2"/>',
            ];
            echo $shapes[$ci % count($shapes)];
            ?>
          </svg>
          <span>
            <span class="cat-name"><?= e($cat['name']) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="shell"><hr class="section-rule"></div>

<!-- ── Why VoiXLib ──────────────────────────────────────── -->
<section class="section">
  <div class="shell">
    <div class="why-grid">
      <div class="reveal">
        <span class="section-num">03 · The idea</span>
        <h2>A quiet room<br>for loud ideas</h2>
        <p style="margin-top:16px;color:var(--ink-2);max-width:38ch;line-height:1.7">
          VoiXLib exists because great stories shouldn’t hide behind cluttered scans and pop-ups.
          We set every title into a reader built for long, comfortable sessions — flipbook for comics, typeset pages for prose.</p>
      </div>
      <div class="why-points reveal">
        <div class="why-point">
          <span class="why-glyph"><?= icon('book-open', 26) ?></span>
          <div>
            <h3>Original illustrated editions</h3>
            <p>Comic pages are drawn as crisp vector art that stays sharp on any screen; novels arrive properly typeset and split into chapters you can actually navigate.</p>
          </div>
        </div>
        <div class="why-point">
          <span class="why-glyph"><?= icon('bookmark', 26) ?></span>
          <div>
            <h3>Your place is remembered</h3>
            <p>Progress and bookmarks persist per chapter — anonymously on this device, or synced to your account when you sign in with Discord.</p>
          </div>
        </div>
        <div class="why-point">
          <span class="why-glyph"><?= icon('globe', 26) ?></span>
          <div>
            <h3>Honest provenance</h3>
            <p>Every edition is VoiXLib’s own: covers designed from each book’s metadata, comic pages drawn in vector art, prose written and typeset in-house.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


