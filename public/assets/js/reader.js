/* Reader: chapter fetching, comfort controls, progress + bookmark, keyboard nav. */
(function () {
  'use strict';

  var rootEl = document.querySelector('[data-reader-root]');
  if (!rootEl) return;

  var bookId = parseInt(rootEl.dataset.bookId, 10);
  var bookTitle = rootEl.dataset.title;
  var externalId = (window.VOIXLIB_READER && window.VOIXLIB_READER.externalId) || '';
  var authed = VX.authed;

  var contentEl = document.getElementById('reader-content');
  var skeletonEl = document.getElementById('reader-skeleton');
  var errorEl = document.getElementById('reader-error');
  var headingEl = document.getElementById('chapter-heading');
  var bodyEl = document.getElementById('chapter-content');
  var bar = document.getElementById('rp-bar');

  var chapters = [];
  var current = 0;
  var saveTimer = null;
  var progressKey = 'voixlib:progress:' + externalId;

  // ── Local progress helpers (anonymous mode) ──────────────────
  function localProgress() {
    try { return JSON.parse(localStorage.getItem(progressKey) || 'null'); }
    catch (e) { return null; }
  }
  function saveLocalProgress(pct, chapter) {
    localStorage.setItem(progressKey, JSON.stringify({ p: pct, c: chapter }));
    var list;
    try { list = JSON.parse(localStorage.getItem('voixlib:progress') || '[]'); } catch (e) { list = []; }
    list = list.filter(function (x) { return x.book_id !== bookId; });
    list.push({ book_id: bookId, progress: pct, chapter: chapter });
    if (list.length > 50) list.shift();
    localStorage.setItem('voixlib:progress', JSON.stringify(list));
    localStorage.setItem('voixlib:pendingSync', '1');
  }

  // ── Comfort settings ─────────────────────────────────────────
  var prefs = Object.assign({
    reader_font: 18, reader_width: 42, reader_leading: 1.7,
    reader_theme: 'light'
  }, VX.serverPrefs || {}, VX.getPrefs());
  applyPrefs();

  function applyPrefs() {
    var rs = document.documentElement.style;
    rs.setProperty('--reader-font', prefs.reader_font + 'px');
    rs.setProperty('--reader-width', prefs.reader_width + 'rem');
    rs.setProperty('--reader-leading', String(prefs.reader_leading));
    rootEl.closest('.reader-main') ? document.body.dataset.readerTheme = '' : null;
    document.documentElement.dataset.readerTheme = prefs.reader_theme;

    ['rf-font', 'rf-leading', 'rf-width'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var map = { 'rf-font': 'reader_font', 'rf-leading': 'reader_leading', 'rf-width': 'reader_width' };
      el.value = prefs[map[id]];
      var out = document.getElementById(id + '-out');
      if (out) out.textContent = id === 'rf-leading' ? Number(el.value).toFixed(1) : el.value + (id === 'rf-font' ? 'px' : 'rem');
    });
    document.querySelectorAll('input[name="rtheme"]').forEach(function (r) {
      r.checked = r.value === prefs.reader_theme;
    });
  }

  // The reader saves prefs through the same endpoint as the settings page.
  function postPrefs(patch) {
    prefs = Object.assign({}, prefs, patch);
    applyPrefs();
    VX.savePrefs(patch);
    if (!authed) return;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      var form = new FormData();
      form.set('_csrf', VX.csrf);
      Object.keys(patch).forEach(function (k) { form.set(k, patch[k]); });
      fetch('/settings.php', { method: 'POST', body: form })
        .then(function (res) { if (!res.ok && res.status !== 302) throw 0; })
        .catch(function () { /* keep local copy authoritative */ });
    }, 700);
  }

  ['rf-font', 'rf-leading', 'rf-width'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    var map = { 'rf-font': 'reader_font', 'rf-leading': 'reader_leading', 'rf-width': 'reader_width' };
    el.addEventListener('input', function () { postPrefsWithoutSave(map[id], parseFloat(el.value)); });
    el.addEventListener('change', function () { postPrefs((map[id] = map[id], definePatch(map[id], parseFloat(el.value)))); });
  });
  function postPrefsWithoutSave(key, value) {
    prefs[key] = value;
    applyPrefs();
  }
  function definePatch(key, value) { var p = {}; p[key] = value; return p; }

  document.querySelectorAll('input[name="rtheme"]').forEach(function (radio) {
    radio.addEventListener('change', function () { postPrefs({ reader_theme: radio.value }); });
  });

  // ── Panels (settings / TOC) ──────────────────────────────────
  var panel = document.getElementById('reader-panel');
  var tocPanel = document.getElementById('toc-panel');
  function openPanel(el, opener) {
    el.hidden = false;
    requestAnimationFrame(function () { el.classList.add('is-open'); });
    if (opener) opener.setAttribute('aria-expanded', 'true');
    el.querySelector('.rs-close').focus();
  }
  function closePanel(el, opener) {
    el.classList.remove('is-open');
    setTimeout(function () { el.hidden = true; }, 280);
    if (opener) opener.setAttribute('aria-expanded', 'false');
  }
  var rsOpen = document.getElementById('rs-open');
  rsOpen.addEventListener('click', function () {
    panel.classList.contains('is-open') ? closePanel(panel, rsOpen) : openPanel(panel, rsOpen);
  });
  document.getElementById('rs-close').addEventListener('click', function () { closePanel(panel, rsOpen); });

  var tocBtn = document.querySelector('[data-reader-toc]');
  tocBtn.addEventListener('click', function () {
    tocPanel.classList.contains('is-open') ? closePanel(tocPanel, tocBtn) : openToc();
  });
  function openToc() {
    renderToc();
    openPanel(tocPanel, tocBtn);
  }
  document.getElementById('toc-close').addEventListener('click', function () { closePanel(tocPanel, tocBtn); });

  [panel, tocPanel].forEach(function (el) {
    el.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closePanel(el); });
  });

  function renderToc() {
    var list = document.getElementById('toc-list');
    list.innerHTML = '';
    chapters.forEach(function (ch, i) {
      var li = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = ch.title || ('Part ' + (i + 1));
      if (i === current) btn.className = 'is-current';
      btn.addEventListener('click', function () { goTo(i); closePanel(tocPanel, tocBtn); });
      li.appendChild(btn);
      list.appendChild(li);
    });
  }

  // ── Chapter navigation ───────────────────────────────────────
  function renderChapter(i) {
    current = Math.max(0, Math.min(chapters.length - 1, i));
    var ch = chapters[current];
    headingEl.textContent = ch.title || ((current > 0) ? 'Continued' : '');
    headingEl.style.display = ch.title ? '' : 'none';
    bodyEl.innerHTML = ch.html;
    window.scrollTo({ top: 0, behavior: 'instant' in window ? 'auto' : 'auto' });
    updateNavButtons();
    renderTocIfOpen();
  }
  function updateNavButtons() {
    document.getElementById('prev-chapter').disabled = current === 0;
    document.getElementById('next-chapter').disabled = current >= chapters.length - 1;
  }
  function renderTocIfOpen() {
    if (tocPanel.classList.contains('is-open')) renderToc();
  }
  function goTo(i) { renderChapter(i); reportProgress(); }

  document.getElementById('prev-chapter').addEventListener('click', function () { goTo(current - 1); });
  document.getElementById('next-chapter').addEventListener('click', function () { goTo(current + 1); });

  document.addEventListener('keydown', function (ev) {
    if (ev.target.closest('input, textarea, select') || ev.metaKey || ev.ctrlKey || ev.altKey) return;
    if (ev.key === 'ArrowRight') goTo(current + 1);
    if (ev.key === 'ArrowLeft') goTo(current - 1);
  });

  // ── Progress tracking ────────────────────────────────────────
  function computePct() {
    var doc = document.documentElement;
    var scrollable = doc.scrollHeight - window.innerHeight;
    if (scrollable <= 0) return Math.round(((current + 1) / chapters.length) * 100);
    var within = window.scrollY / scrollable;
    var base = current / chapters.length;
    return Math.round(Math.min(100, (base + within / chapters.length) * 100));
  }
  function reportProgress() {
    var pct = computePct();
    if (bar) bar.style.width = pct + '%';
    saveLocalProgressThrottled(pct, current);
  }
  function saveLocalProgressThrottled(pct, chIdx) {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      saveLocalProgress(pct, chIdx);
      if (authed) {
        VX.api('/api/progress.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': VX.csrf },
          body: { book_id: bookId, progress: pct, chapter: chIdx, location: (chapters[chIdx].title || '').slice(0, 80) }
        }).catch(function () { /* offline-tolerant */ });
      }
    }, 900);
  }
  window.addEventListener('scroll', function () { reportProgress(); }, { passive: true });

  // ── Bookmarks & library fabs ─────────────────────────────────
  var fabBm = document.getElementById('fab-bookmark');
  fabBm.addEventListener('click', function () {
    var loc = 'ch' + (current + 1) + ':' + Math.round(window.scrollY);
    if (!authed) {
      var list;
      try { list = JSON.parse(localStorage.getItem('voixlib:bookmarks') || '[]'); } catch (e) { list = []; }
      list.push({ book_id: bookId, location: loc, label: bookTitle + ' — part ' + (current + 1) });
      try { localStorage.setItem('voixlib:bookmarks', JSON.stringify(list)); } catch (e) { /* full */ }
      localStorage.setItem('voixlib:pendingSync', '1');
      VX.toast('Bookmarked on this device. Sign in to sync.');
      popFab(fabBm);
      return;
    }
    VX.api('/api/bookmark.php', {
      method: 'POST',
      body: { action: 'add', book_id: bookId, location: loc, label: (chapters[current].title || 'Part ' + (current + 1)) }
    }).then(function () { VX.toast('Position bookmarked.'); popFab(fabBm); })
      .catch(function () { VX.toast('Could not save bookmark.', 'error'); });
  });
  function popFab(fab) {
    fab.animate(
      [{ transform: 'scale(1)' }, { transform: 'scale(1.25) rotate(-6deg)' }, { transform: 'scale(1)' }],
      { duration: 420, easing: 'cubic-bezier(.3,1.6,.5,1)' }
    );
  }

  var fabLib = document.getElementById('fab-library');
  fabLib.addEventListener('click', function () {
    if (!authed) return location.href = '/auth/discord.php?next=' + encodeURIComponent(location.pathname + location.search);
    VX.api('/api/library.php', { method: 'POST', body: { action: 'add', book_id: bookId, status: 'reading' } })
      .then(function () { VX.toast('Saved to “Currently reading”.'); popFab(fabLib); })
      .catch(function () { VX.toast('Library service unreachable.', 'error'); });
  });

  // ── Boot: fetch content ──────────────────────────────────────
  fetch('/api/content.php?id=' + encodeURIComponent(externalId))
    .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
    .then(function (_a) {
      var ok = _a.ok, data = _a.d;
      if (!ok || !data.chapters) throw new Error(data.error || 'failed');
      chapters = data.chapters;
      skeletonEl.remove();

      // resume position
      var resume = null;
      if (authed) {
        // server progress is embedded via data attribute set at render time? fetched separately:
        resume = null; // resolved below
      }
      fetchResume().then(function (serverPos) {
        var local = localProgress();
        var pos = serverPos || local || { c: 0 };
        var jump = sessionStorage.getItem('voixlib:jumpto');
        if (jump !== null) { pos = { c: parseInt(jump, 10) || 0 }; sessionStorage.removeItem('voixlib:jumpto'); }
        renderChapter(parseInt(pos.c || 0, 10));
        contentEl.hidden = false;
        reportProgress();
        if (pos && parseInt(pos.p || 0, 10) > 3 && parseInt(pos.p || 0, 10) < 98) {
          VX.toast('Resumed at part ' + (parseInt(pos.c || 0, 10) + 1) + '.');
        }
      });
    })
    .catch(function () {
      skeletonEl.remove();
      errorEl.hidden = false;
    });

  function fetchResume() {
    if (!authed) return Promise.resolve(null);
    return fetch('/api/progress.php?book_id=' + bookId)
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (d) { return d && d.progress ? d.progress : null; })
      .catch(function () { return null; });
  }

  // deep-link from a library bookmark (?bm=ch2%3A120)
  var bmParam = new URLSearchParams(location.search).get('bm');
  if (bmParam) {
    var m = /^ch(\d+)/.exec(bmParam);
    if (m) sessionStorage.setItem('voixlib:jumpto', m[1] - 1);
  }
})();
