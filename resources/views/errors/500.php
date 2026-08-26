<?php component('states'); ?>
<div class="shell">
  <?php render_state('offline', 'Something came loose in the stacks',
      $message ?? 'An unexpected error interrupted this page. It’s us, not you — try again in a moment.',
      '/', 'Return to the library'); ?>
</div>

