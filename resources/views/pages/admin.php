<?php
/** Admin dashboard. Server-guarded; see AdminController::guard(). */
component('icons');
?>
<div class="shell">
  <header class="page-head">
    <span class="section-num">Back office</span>
    <h1>Catalog admin</h1>
    <p class="lede">Add books from Project Gutenberg IDs, curate categories, feature titles. Every action is validated server-side.</p>
  </header>

  <?php if ($notice): ?>
    <div class="notice notice-<?= e($notice['type']) ?>"><?= e($notice['text']) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:26px" class="admin-grid">
    <div>
      <form class="admin-panel" method="post" action="/admin.php?action=add_gutenberg">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="add_gutenberg">
        <h2 style="font-size:19px;margin-bottom:6px">Add from Gutenberg</h2>
        <p class="setting-help">Fetches real metadata, verifies the cover, enriches via Open Library, then inserts.</p>
        <div class="field">
          <label for="gid">Project Gutenberg ID</label>
          <input class="admin-input" id="gid" name="gutenberg_id" type="number" min="1" max="99999"
                 placeholder="e.g. 1342 for Pride and Prejudice" required>
        </div>
        <fieldset class="field">
          <legend class="field"><label>Categories (optional)</label></legend>
          <div class="checks">
            <?php foreach (($categories ?? []) as $c): ?>
              <label class="check-pill"><input type="checkbox" name="categories[]" value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <button class="btn btn-solid" type="submit">Add book</button>
      </form>

      <div class="admin-panel">
        <h2 style="font-size:19px;margin-bottom:6px">Edit / remove a title</h2>
        <p class="setting-help">Click a row on the right to load it here — or edit inline below the table.</p>
        <div id="admin-edit-slot"></div>
      </div>
    </div>

    <div class="admin-panel">
      <h2 style="font-size:19px;margin-bottom:14px">Latest additions</h2>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead><tr><th>Title</th><th>Author</th><th>Featured</th><th></th></tr></thead>
          <tbody id="admin-rows">
            <?php foreach ((array)($books ?? []) as $row): ?>
              <tr data-admin-book='<?= e(json_encode([
                  'id' => (int)$row['id'],
                  'title' => (string)$row['title'],
                  'author' => (string)$row['author'],
                  'featured' => (bool)$row['featured'],
              ])) ?>'>
                <td><?= e($row['title']) ?></td>
                <td><?= e($row['author']) ?></td>
                <td><?= !empty($row['featured']) ? '★' : '—' ?></td>
                <td>
                  <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:12.5px"
                          data-edit-book="<?= (int)$row['id'] ?>">Load</button>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$books): ?><tr><td colspan="4" style="color:var(--ink-2)">Nothing in the catalog yet — add your first book.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <template id="tpl-edit-form">
    <form method="post" action="/admin.php" class="edit-form" style="margin-top:18px;border-top:1px solid var(--line);padding-top:18px">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="book_id" value="">
      <div class="field"><label>Title</label><input class="admin-input" name="title"></div>
      <div class="field"><label>Author</label><input class="admin-input" name="author"></div>
      <div class="field"><label>Description</label><textarea class="admin-input" name="description"></textarea></div>
      <div class="field"><label>Publication year</label><input class="admin-input" type="number" name="publication_year"></div>
      <div class="field"><label>Page count</label><input class="admin-input" type="number" name="page_count"></div>
      <label class="check-pill"><input type="checkbox" name="featured" value="1"> Featured</label>
      <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn btn-solid" type="submit">Save changes</button>
        <button class="btn btn-ghost danger-delete" type="submit">Remove book</button>
      </div>
    </form>
  </template>
</div>

