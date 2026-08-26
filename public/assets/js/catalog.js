/* Explore + search catalog: debounced fetch, skeletons, grid/list, load-more, URL sync. */
(function () {
  'use strict';

  var toolbar = document.getElementById('catalog-toolbar');
  var resultsEl = document.getElementById('catalog-results');
  if (!toolbar || !resultsEl) return;

  var loadMoreBtn = document.getElementById('load-more');
  var countEl = document.getElementById('results-count');
  var view = resultsEl.classList.contains('catalog-list') ? 'list' : 'grid';
  var busy = false;
  var debounceTimer = null;

  function params(extra) {
    var fd = new FormData(toolbar);
    var p = {};
    fd.forEach(function (v, k) { if (String(v) !== '') p[k] = v; });
    p.view = view;
    p.page = (extra && extra.page) || 1;
    if (p.year_from !== undefined) p.year_from = parseInt(p.year_from, 10) || '';
    if (p.year_to !== undefined) p.year_to = parseInt(p.year_to, 10) || '';
    Object.keys(p).forEach(function (k) { if (p[k] === '' ) delete p[k]; });
    return p;
  }

  function qs(p) {
    var q = new URLSearchParams();
    Object.keys(p).forEach(function (k) { if (p[k] !== '' && p[k] != null) q.set(k, p[k]); });
    var s = q.toString();
    return s ? '?' + s : '';
  }

  function coverSrc(b) {
    return b.cover_url || ('/cover.php?' + new URLSearchParams({ t: b.title, a: b.author, g: b.type_label || '' }).toString());
  }

  function cardHTML(b) {
    var cat = b.type_label ? '<span>' + escapeHTML(b.type_label) + '</span> ' : '';
    var year = b.year ? '<span>' + b.year + '</span>' : '';
    var score = b.score ? '<span class="pct">' + Math.round(b.score / 10) + '</span>' : '';
    return '' +
      '<article class="book-card">' +
      ' <a class="book-card-link" href="' + escapeHTML(b.url_detail) + '">' +
      '  <div class="cover' + (b.cover_url ? '' : ' is-generated') + '">' +
      '   <img src="' + coverSrc(b) + '" alt="" loading="lazy" decoding="async" width="400" height="600">' +
      '   <span class="cover-spine"></span>' +
      '<span class="cover-flag">' + escapeHTML(b.type_label || 'Media') + '</span>' +
      '  </div>' +
      '  <div class="book-meta"><h3 class="book-title">' + escapeHTML(b.title) + '</h3>' +
      '   <p class="book-author">' + escapeHTML(b.author) + '</p>' +
      '   <p class="book-sub">' + score + cat + year + '</p></div>' +
      ' </a></article>';
  }

  function rowHTML(b) {
    var excerpt = b.excerpt ? '<span class="row-excerpt">' + escapeHTML(b.excerpt) + '…</span>' : '';
    return '<a class="book-row" href="' + escapeHTML(b.url_detail) + '">' +
      '<span class="cover"><img src="' + coverSrc(b) + '" alt="" loading="lazy"></span>' +
      '<span><span class="row-title">' + escapeHTML(b.title) + '</span>' +
      '<span class="row-author">' + escapeHTML(b.author) + (b.year ? ' · ' + b.year : '') + '</span>' + excerpt + '</span>' +
      '<span class="chip is-active">' + escapeHTML(b.type_label || 'Media') + '</span>' +
      '</a>';
  }

  function skeletonHTML() {
    var out = '';
    for (var i = 0; i < 12; i++) {
      out += '<article class="book-card"><div class="skeleton skel-cover"></div>' +
        '<div class="skeleton skel-line w60"></div><div class="skeleton skel-line w40"></div></article>';
    }
    return out;
  }

  function setState(state, message) {
    var art = {
      search: '<svg viewBox="0 0 220 140" class="state-art"><path d="M20 118h180" stroke="currentColor" stroke-width="1.6" fill="none"/><circle cx="96" cy="66" r="34" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m122 92 26 26" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M82 58c8-8 22-8 30 0" fill="none" stroke="var(--accent)" stroke-width="1.7"/></svg>',
      offline: '<svg viewBox="0 0 220 140" class="state-art"><path d="M20 118h180" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M40 78c38-30 102-30 140 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-dasharray="4 5"/><path d="m150 40 24 24M174 40l-24 24" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" fill="none"/></svg>',
      empty: '<svg viewBox="0 0 220 140" class="state-art"><path d="M20 118h180" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M60 118V96l50-18 50 18v22" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>'
    }[state];
    resultsEl.innerHTML = '<div class="state-block" role="' + (state === 'offline' ? 'alert' : 'status') + '">' +
      art + '<h3 class="state-heading">' + escapeHTML(message.heading) + '</h3>' +
      '<p class="state-body">' + escapeHTML(message.body) + '</p></div>';
  }

  function fetchPage(page, append) {
    if (busy) return;
    busy = true;
    if (!append) resultsEl.innerHTML = skeletonHTML();
    if (loadMoreBtn) { loadMoreBtn.disabled = true; loadMoreBtn.textContent = 'Memuat…'; }

    var url = '/api/books.php' + qs(params({ page: page }));
    history.replaceState(null, '', location.pathname + qs(Object.assign(params({ page: page }), {})));

    fetch(url)
      .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
      .then(function (_a) {
        var ok = _a.ok, data = _a.d;
        if (!ok) throw new Error(data.error || 'failed');
        var books = data.books || [];
        var html = books.map(view === 'list' ? rowHTML : cardHTML).join('');

        if (append) {
          var frag = document.createElement('template');
          frag.innerHTML = html;
          resultsEl.appendChild(frag.content);
        } else {
          resultsEl.innerHTML = html;
          if (!books.length) {
          var filtered = !!new FormData(toolbar).get('type') || !!new FormData(toolbar).get('genre');
          setState(filtered ? 'search' : 'empty', filtered
            ? { heading: 'Rak ini masih kosong', body: 'Tidak ada judul yang cocok dengan filter itu — longgarkan satu filter dan rak akan terisi lagi.' }
            : { heading: 'Belum ada judul', body: 'Katalog belum menampilkan apa pun saat ini.' });
        }
        }
        if (countEl) countEl.textContent = (data.total != null ? numberFormat(data.total) + (data.total === 1 ? ' judul' : ' judul') : '');

        // manage load-more button
        var shown = resultsEl.querySelectorAll('.book-card, .book-row').length;
        var wrap = document.getElementById('load-more-wrap');
        if (data.total != null && shown < data.total) {
          if (loadMoreBtn) {
            loadMoreBtn.dataset.nextPage = String((data.page || page) + 1);
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = 'Muat lebih banyak';
          } else if (wrap) {
            wrap.innerHTML = '<button class="btn btn-ghost" id="load-more" data-next-page="' + ((data.page || page) + 1) + '">Muat lebih banyak</button>';
            loadMoreBtn = document.getElementById('load-more');
            bindLoadMore();
          }
        } else if (loadMoreBtn) {
          loadMoreBtn.remove();
          loadMoreBtn = null;
        }
        busy = false;
      })
      .catch(function () {
        busy = false;
        setState('offline', { heading: 'Katalog tidak bisa dihubungi', body: 'Penyedia data tidak menjawab tepat waktu. Ini bukan kesalahan perangkatmu — coba lagi.' });
        if (loadMoreBtn) { loadMoreBtn.disabled = false; loadMoreBtn.textContent = 'Load more'; }
      });
  }

  function bindLoadMore() {
    if (!loadMoreBtn) return;
    loadMoreBtn.addEventListener('click', function () {
      fetchPage(parseInt(loadMoreBtn.dataset.nextPage || '2', 10), true);
    });
  }
  bindLoadMore();

  // auto-submit controls
  toolbar.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { fetchPage(1, false); });
  });
  toolbar.querySelectorAll('input[type="number"]').forEach(function (el) {
    el.addEventListener('change', function () { fetchPage(1, false); });
  });

  // view toggle
  toolbar.querySelectorAll('.view-toggle button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      view = btn.dataset.view;
      toolbar.querySelectorAll('.view-toggle button').forEach(function (b) {
        b.setAttribute('aria-pressed', String(b === btn));
      });
      resultsEl.className = view === 'list' ? 'catalog-list' : 'catalog-grid';
      fetchPage(1, false);
    });
  });

  function numberFormat(n) {
    try { return new Intl.NumberFormat().format(n); }
    catch (e) { return String(n); }
  }
  function escapeHTML(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
})();
