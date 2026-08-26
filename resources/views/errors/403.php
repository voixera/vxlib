<?php component('states'); ?>
<div class="shell">
  <?php render_state('error', 'That door is locked',
      'Your account doesn’t have access to this area. If you believe that’s a mistake, ask an administrator to check ADMIN_DISCORD_IDS.',
      '/', 'Back to safety'); ?>
</div>

