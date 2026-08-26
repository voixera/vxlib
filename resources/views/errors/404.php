<?php component('states'); ?>
<div class="shell">
  <?php render_state('search', 'This shelf doesn’t exist',
      $message ?? 'The page you were after isn’t on any of our shelves. Maybe it moved, maybe it never was.',
      '/', 'Return to the library'); ?>
</div>

