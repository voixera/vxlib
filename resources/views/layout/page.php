<?php
/** Main layout wrapper. Expects: $__template, $__page (title/description/activeNav/…). */
$page = $__page;
$template = preg_replace('/[^a-z0-9_\/\-]/i', '', (string)$__template);
$prefs = Prefs::current(Auth::user());
$csrfToken = Security::csrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title'] ?? 'VoiXLib') ?></title>
<meta name="description" content="<?= e($page['description'] ?? 'Your library, beyond the shelf.') ?>">
<meta name="theme-color" content="#F4F1EA" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#161513" media="(prefers-color-scheme: dark)">
<link rel="canonical" href="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<meta property="og:site_name" content="VoiXLib">
<meta property="og:title" content="<?= e($page['title'] ?? 'VoiXLib') ?>">
<meta property="og:description" content="<?= e($page['description'] ?? '') ?>">
<meta property="og:type" content="<?= e($page['ogType'] ?? 'website') ?>">
<meta property="og:url" content="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<?php if (!empty($page['ogImage'])): ?>
<meta property="og:image" content="<?= e($page['ogImage']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($page['title'] ?? 'VoiXLib') ?>">
<meta name="twitter:description" content="<?= e($page['description'] ?? '') ?>">
<meta name="twitter:image" content="<?= e($page['ogImage']) ?>">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<?php foreach ((array)($page['styles'] ?? []) as $css): ?>
<link rel="stylesheet" href="<?= asset('css/' . $css) ?>">
<?php endforeach; ?>
<script>
/* Theme boot — inline to avoid a flash of the wrong theme. */
(function () {
  try {
    var stored = localStorage.getItem('voixlib:prefs');
    var prefs = stored ? JSON.parse(stored) : {};
    var theme = prefs.theme || 'auto';
    var dark = theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    if (prefs.motion === 'reduced' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      document.documentElement.dataset.motion = 'reduced';
    }
  } catch (e) { /* stay on defaults */ }
})();
</script>
</head>
<body class="<?= e($page['bodyClass'] ?? '') ?>" data-page="<?= e($template) ?>"
      data-prefs='<?= e(Prefs::clientBootstrap($prefs)) ?>'
      data-authed="<?= Auth::check() ? '1' : '0' ?>">
<a class="skip-link" href="#main">Skip to content</a>

<?php if (empty($page['chromeless'])): ?>
  <?php view('layout/header', ['activeNav' => $page['activeNav'] ?? '', 'user' => Auth::user()]); ?>
<?php endif; ?>

<main id="main" class="<?= !empty($page['chromeless']) ? 'reader-main' : 'site-main' ?>">
  <?php view($template, $vars ?? []); ?>
</main>

<?php if (empty($page['chromeless'])): view('layout/footer'); endif; ?>

<div id="toast-root" aria-live="polite"></div>
<script>window.VOIXLIB = { csrf: <?= json_encode($csrfToken) ?>, authed: <?= Auth::check() ? 'true' : 'false' ?>, base: <?= json_encode(rtrim((string)Config::get('APP_URL',''), '/')) ?> };</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php if (!empty($page['scripts'])): foreach ((array)$page['scripts'] as $script): ?>
<script src="<?= asset('js/' . $script) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
