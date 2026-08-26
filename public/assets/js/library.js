/* Library page: tabs, status change, remove. */
(function () {
  'use strict';

  // Tabs
  document.querySelectorAll('[data-shelf-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('[data-shelf-tab]').forEach(function (t) { t.setAttribute('aria-selected', String(t === tab)); });
      document.querySelectorAll('.library-panel').forEach(function (p) {
        p.classList.toggle('is-active', p.id === 'panel-' + tab.dataset.shelfTab);
      });
    });
  });

  // Status change
  document.querySelectorAll('select[data-status-for]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var bookId = sel.dataset.statusFor;
      VX.api('/api/library.php', {
        method: 'POST',
        body: { action: 'add', book_id: parseInt(bookId, 10), status: sel.value }
      }).then(function () { VX.toast('Moved shelf.'); })
        .catch(function () { VX.toast('Could not update — try again.', 'error'); });
    });
  });
  document.querySelectorAll('.inline-status-form').forEach(function (f) {
    f.addEventListener('submit', function (ev) { ev.preventDefault(); }); // JS handles it
  });

  // Remove from library
  document.querySelectorAll('[data-remove-from-library]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var bookId = parseInt(btn.dataset.removeFromLibrary, 10);
      VX.api('/api/library.php', { method: 'POST', body: { action: 'remove', book_id: bookId } })
        .then(function () {
          var row = btn.closest('[data-lib-row]');
          if (row) {
            row.style.transition = 'opacity .25s ease, transform .25s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateY(6px)';
            setTimeout(function () { row.remove(); }, 240);
          }
          VX.toast('Removed.');
        })
        .catch(function () { VX.toast('Could not remove — try again.', 'error'); });
    });
  });

  // Delete bookmark
  document.querySelectorAll('[data-remove-bookmark]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      VX.api('/api/bookmark.php', { method: 'POST', body: { action: 'remove', bookmark_id: parseInt(btn.dataset.removeBookmark, 10) } })
        .then(function () {
          var row = btn.closest('[data-bookmark-id]');
          if (row) row.remove();
          VX.toast('Bookmark deleted.');
        })
        .catch(function () { VX.toast('Could not delete.', 'error'); });
    });
  });
})();
