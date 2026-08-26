/* Book detail interactions: library add, bookmark pop, share. */
(function () {
  'use strict';

  var libBtn = document.querySelector('.action-library');
  var bmBtn = document.querySelector('.action-bookmark');
  var shareBtn = document.querySelector('.action-share');

  function requireAuth(btn) {
    if (btn.dataset.needsAuth === '1') {
      VX.toast('Sign in with Discord to use your library.', 'error');
      return true;
    }
    return false;
  }

  if (libBtn) {
    libBtn.addEventListener('click', function () {
      if (requireAuth(libBtn)) return location.href = '/auth/discord.php?next=' + encodeURIComponent(location.pathname + location.search);
      var status = libBtn.dataset.status;
      var removing = !!status;
      VX.api('/api/library.php', {
        method: 'POST',
        body: { action: removing ? 'remove' : 'add', book_id: parseInt(libBtn.dataset.bookId, 10), status: 'want_to_read' }
      }).then(function (res) {
        libBtn.dataset.status = removing ? '' : (res.status || 'want_to_read');
        libBtn.querySelector('.al-label').textContent = removing ? 'Add to library' : 'On your shelf';
        VX.toast(removing ? 'Removed from your library.' : 'Added to “Want to read”.');
      }).catch(function () { VX.toast('Could not reach the library service.', 'error'); });
    });
  }

  if (bmBtn) {
    bmBtn.addEventListener('click', function () {
      if (requireAuth(bmBtn)) return location.href = '/auth/discord.php?next=' + encodeURIComponent(location.pathname + location.search);
      var saved = bmBtn.getAttribute('aria-pressed') === 'true';
      bmBtn.classList.remove('bm-pop');
      void bmBtn.offsetWidth; // restart animation
      bmBtn.classList.add('bm-pop');
      VX.api('/api/bookmark.php', {
        method: 'POST',
        body: saved
          ? { action: 'list', book_id: parseInt(bmBtn.dataset.bookId, 10) }
          : { action: 'add', book_id: parseInt(bmBtn.dataset.bookId, 10), location: 'book-detail' }
      }).then(function (res) {
        // toggle semantics: when un-saving we need the latest id to delete — simplest: list & remove all detail marks
        if (!saved) {
          bmBtn.setAttribute('aria-pressed', 'true');
          bmBtn.innerHTML = '<svg class="icon icon-bookmark-filled" width="19" height="19" viewBox="0 0 24 24"><path d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="currentColor" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
          VX.toast('Bookmarked.');
          return;
        }
        var marks = (res.bookmarks || []).filter(function (m) { return m.location === 'book-detail'; });
        if (!marks.length) {
          bmBtn.setAttribute('aria-pressed', 'false');
          bmBtn.innerHTML = '<svg class="icon icon-bookmark" width="19" height="19" viewBox="0 0 24 24"><path d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
          VX.toast('Bookmark removed.');
          return Promise.all(marks.map(function () { return null; }));
        }
        return Promise.all(marks.map(function (m) {
          return VX.api('/api/bookmark.php', { method: 'POST', body: { action: 'remove', bookmark_id: m.id } });
        })).then(function () {
          bmBtn.setAttribute('aria-pressed', 'false');
          bmBtn.innerHTML = '<svg class="icon icon-bookmark" width="19" height="19" viewBox="0 0 24 24"><path d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
          VX.toast('Bookmark removed.');
        });
      }).catch(function () { VX.toast('Bookmark service unreachable.', 'error'); });
    });
  }

  if (shareBtn) {
    shareBtn.addEventListener('click', function () {
      var title = shareBtn.dataset.title;
      var url = shareBtn.dataset.url;
      if (navigator.share) {
        navigator.share({ title: title + ' — VoiXLib', url: url }).catch(function () { /* user dismissed */ });
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () { VX.toast('Link copied to clipboard.'); });
      } else {
        VX.toast(url);
      }
    });
  }
})();
