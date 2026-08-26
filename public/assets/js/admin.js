/* Admin: load a book into the inline edit form; delete guarded by confirm(). */
(function () {
  'use strict';

  var slot = document.getElementById('admin-edit-slot');
  var tpl = document.getElementById('tpl-edit-form');
  if (!slot || !tpl) return;

  document.querySelectorAll('[data-edit-book]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tr = btn.closest('[data-admin-book]');
      if (!tr) return;
      var book;
      try { book = JSON.parse(tr.dataset.adminBook); } catch (e) { return; }

      slot.innerHTML = '';
      var frag = tpl.content.cloneNode(true);
      var form = frag.querySelector('form');
      form.querySelector('[name="book_id"]').value = book.id;
      form.querySelector('[name="title"]').value = book.title || '';
      form.querySelector('[name="author"]').value = book.author || '';
      form.querySelector('[name="featured"]').checked = !!book.featured;

      var del = frag.querySelector('.danger-delete');
      del.type = 'button';
      del.textContent = 'Remove book';
      del.addEventListener('click', function () {
        if (!confirm('Remove “' + (book.title || '') + '” from the catalog? This cannot be undone.')) return;
        form.querySelector('[name="action"]').value = 'delete';
        form.submit();
      });

      slot.appendChild(frag);
      form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });
})();
