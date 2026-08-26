<?php
/** Settings. Works without JS (plain form POST) and with JS (instant apply). */
component('icons');
?>
<div class="shell">
  <header class="page-head">
    <span class="section-num">Preferences</span>
    <h1>Settings</h1>
    <p class="lede">Theme and reading comfort. <?= $user ? 'Saved to your VoiXLib account.' : 'Saved on this device — sign in to sync them.' ?></p>
  </header>

  <?php if ($saved): ?>
    <div class="save-flash" style="margin-top:22px"><?= icon('check', 17) ?> Preferences saved<?= $user ? ' to your account' : '' ?>.</div>
  <?php endif; ?>

  <form class="settings-form" method="post" action="/settings.php" id="settings-form">
    <?= Security::csrfField() ?>

    <fieldset class="setting-group" style="border:none;padding-inline:0">
      <legend>Appearance</legend>
      <p class="setting-help">How VoiXLib looks across the whole site.</p>
      <div class="seg" role="radiogroup">
        <?php foreach ([['auto', 'Match system'], ['light', 'Paper'], ['dark', 'Charcoal']] as [$v, $l]): ?>
          <label><input type="radio" name="theme" value="<?= $v ?>" data-setting="theme"
              <?= ($prefs['theme'] ?? 'auto') === $v ? 'checked' : '' ?>><span><?= $l ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="setting-group" style="border:none;padding-inline:0">
      <legend>Motion</legend>
      <p class="setting-help">Scenery animation, parallax and reveal effects.</p>
      <div class="seg">
        <?php foreach ([['on', 'Full motion'], ['reduced', 'Reduced']] as [$v, $l]): ?>
          <label><input type="radio" name="motion" value="<?= $v ?>" data-setting="motion"
              <?= ($prefs['motion'] ?? 'on') === $v ? 'checked' : '' ?>><span><?= $l ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="setting-group" style="border:none;padding-inline:0">
      <legend>Reader defaults</legend>
      <p class="setting-help">Starting point when you open any book. Fine-tune inside the reader too.</p>

      <div class="range-row">
        <label for="rf-font" style="min-width:110px;font-size:14px">Text size</label>
        <input type="range" id="rf-font" name="reader_font" min="14" max="24" step="1"
               value="<?= (int)$prefs['reader_font'] ?>" data-setting="reader_font">
        <output for="rf-font"><?= (int)$prefs['reader_font'] ?>px</output>
      </div>
      <div class="range-row">
        <label for="rf-width" style="min-width:110px;font-size:14px">Page width</label>
        <input type="range" id="rf-width" name="reader_width" min="34" max="60" step="1"
               value="<?= (int)$prefs['reader_width'] ?>" data-setting="reader_width">
        <output><?= (int)$prefs['reader_width'] ?>rem</output>
      </div>
      <div class="range-row">
        <label for="rf-leading" style="min-width:110px;font-size:14px">Line height</label>
        <input type="range" id="rf-leading" name="reader_leading" min="1.4" max="2.2" step="0.1"
               value="<?= e((string)$prefs['reader_leading']) ?>" data-setting="reader_leading">
        <output><?= e((string)$prefs['reader_leading']) ?></output>
      </div>
      <div style="margin-top:18px" class="seg" role="radiogroup">
        <?php foreach ([['light', 'Paper'], ['sepia', 'Sepia'], ['dark', 'Dark']] as [$v, $l]): ?>
          <label><input type="radio" name="reader_theme" value="<?= $v ?>" data-setting="reader_theme"
              <?= ($prefs['reader_theme'] ?? 'light') === $v ? 'checked' : '' ?>><span><?= $l ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="settings-savebar">
      <button class="btn btn-solid" type="submit">Save preferences</button>
      <?php if (!$user): ?>
        <a class="btn btn-discord" href="/auth/discord.php?next=/settings.php"><?= icon('discord', 16) ?> Sign in to sync</a>
      <?php endif; ?>
    </div>
  </form>
</div>

