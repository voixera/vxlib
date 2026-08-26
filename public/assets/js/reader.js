/* Pembaca flipbook VoiXLib — teks domain publik, animasi membalik halaman 3D.
   Teks bab dipaginasi menjadi halaman berukuran tetap, lalu ditumpuk sebagai
   lembar .fb-sheet yang berputar pada poros tulang buku. Keyboard/klik/swipe. */
(function () {
  'use strict';

  var dataEl = document.getElementById('reader-data');
  var shell = document.getElementById('reader-shell');
  if (!dataEl || !shell) return;

  var DATA;
  try { DATA = JSON.parse(dataEl.textContent || '{}'); } catch (e) { DATA = null; }

  function showError() {
    var sk = document.getElementById('fb-skeleton');
    if (sk) sk.remove();
    var eb = document.getElementById('reader-error');
    if (eb) eb.hidden = false;
    var fl = document.getElementById('flipbook');
    if (fl) fl.hidden = true;
  }
  if (!DATA || !DATA.chapters) return showError();
  var chapters = DATA.chapters.filter(function (c) { return (c.html || '').trim() !== ''; });
  if (!chapters.length) return showError();

  var bookKey = 'voixlib:progress:' + DATA.bookId;
  var bookEl = document.getElementById('fb-book');
  var stage = document.getElementById('fb-stage');
  var wrapEl = document.getElementById('flipbook');
  var bar = document.getElementById('rp-bar');
  var prevBtn = document.getElementById('fb-prev');
  var nextBtn = document.getElementById('fb-next');

  /* ── state ── */
  var chIdx = 0, pageIdx = 0;
  var pages = [];
  var sheets = [];
  var cur = 0;
  var animEl = null;
  var fontScale = 1;

  /* ── util ── */
  function $(id) { return document.getElementById(id); }
  function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }
  function throttle(fn, ms) {
    var t = null;
    return function () {
      var a = arguments, s = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(s, a); }, ms);
    };
  }

  function overallPct() {
    var frac = pages.length ? (pageIdx + 1) / pages.length : 0;
    return clamp(Math.round(((chIdx + frac) / chapters.length) * 100), 1, 100);
  }

  function savePos() {
    try {
      localStorage.setItem(bookKey, JSON.stringify({ c: chIdx, p: pageIdx, pct: overallPct(), t: Date.now() }));
    } catch (e) { /* abaikan */ }
  }
  var saveSoon = throttle(savePos, 500);

  function loadPos() {
    try {
      var v = JSON.parse(localStorage.getItem(bookKey) || 'null');
      if (!v || typeof v.c !== 'number') return null;
      return { c: clamp(v.c | 0, 0, chapters.length - 1), p: Math.max(0, v.p | 0), pct: v.pct | 0 };
    } catch (e) { return null; }
  }

  /* ── geometri halaman ── */
  var M = { pw: 320, ph: 480, padX: 28, padY: 34 };

  function computeMetrics() {
    var r = bookEl.getBoundingClientRect();
    if (!r.width || !r.height) return false;
    var singleLeaf = window.matchMedia('(max-width: 900px)').matches;
    M.pw = singleLeaf ? r.width : r.width / 2;
    M.ph = r.height;
    M.padX = Math.round(clamp(M.pw * 0.10, 18, 56));
    M.padY = Math.round(clamp(M.ph * 0.085, 24, 64));
    var rs = document.documentElement.style;
    rs.setProperty('--pg-inset', M.padY + 'px ' + M.padX + 'px');
    return true;
  }

  var measureHost = document.createElement('div');
  measureHost.className = 'fb-measure';
  measureHost.setAttribute('aria-hidden', 'true');
  document.body.appendChild(measureHost);

  /* ── paginasi ── */
  function paginate(html) {
    var boxW = M.pw - M.padX * 2;
    var boxH = M.ph - M.padY * 2;

    measureHost.innerHTML = '';
    var host = document.createElement('div');
    host.className = 'pg-flow';
    host.style.width = boxW + 'px';
    host.style.minHeight = boxH + 'px';
    measureHost.appendChild(host);

    var out = [];

    function newFlow() {
      host.innerHTML = '';
      var f = document.createElement('div');
      f.className = 'pg-col';
      host.appendChild(f);
      return f;
    }
    var flow = newFlow();

    function pushPage() {
      if (flow.childNodes.length) out.push('<div class="pg-col">' + flow.innerHTML + '</div>');
      flow = newFlow();
      host.appendChild(flow);
    }
    function fits(el) {
      flow.appendChild(el);
      var ok = flow.offsetHeight <= boxH;
      if (!ok) flow.removeChild(el);
      return ok;
    }
    function forceFit(el) {
      pushPage();
      flow.appendChild(el);
    }

    function splitWords(text) {
      var words = String(text).split(/\s+/).filter(function (w) { return w !== ''; });
      var p = document.createElement('p');
      for (var i = 0; i < words.length; i++) {
        p.appendChild(document.createTextNode(words[i] + ' '));
        flow.appendChild(p);
        if (flow.offsetHeight <= boxH) continue;
        flow.removeChild(p);
        p.removeChild(p.lastChild);
        i--; // ulangi kata ini di halaman baru
        pushPage();
        p.appendChild(document.createTextNode(words[i] + ' '));
        flow.appendChild(p);
        if (flow.offsetHeight > boxH) {
          flow.removeChild(p); // kata tunggal pun meluap: terima di halaman segar tanpa cek
          p.appendChild(document.createTextNode(''));
          flow.appendChild(p);
          i++; // lanjut
        }
      }
      if (p.childNodes.length) flow.appendChild(p);
    }

    function handleBlock(blk) {
      if (blk.nodeType === 3) {
        var txt = String(blk.textContent || '').replace(/\s+/g, ' ').trim();
        if (txt === '') return;
        blk = document.createElement('p');
        blk.textContent = txt;
      }
      if (blk.nodeType !== 1) return;

      if (fits(blk)) return;

      // blok teks panjang: pecah per kata
      if (!blk.children.length && String(blk.textContent).trim().length > 60) {
        splitWords(String(blk.textContent));
        return;
      }
      // kontainer (section/div/blockquote): proses anak-anaknya
      if (blk.children.length) {
        Array.prototype.slice.call(blk.childNodes).forEach(function (child) {
          if (child.nodeType === 3) {
            var t = String(child.textContent || '').replace(/\s+/g, ' ').trim();
            if (t === '') return;
            child = document.createElement('p');
            child.textContent = t;
          }
          if (fits(child)) return;
          if (!child.children.length && String(child.textContent).trim().length > 40) splitWords(String(child.textContent));
          else forceFit(child);
        });
        return;
      }
      forceFit(blk);
    }

    var source = document.createElement('div');
    source.innerHTML = html;
    Array.prototype.slice.call(source.childNodes).forEach(handleBlock);
    if (flow.childNodes.length) out.push('<div class="pg-col">' + flow.innerHTML + '</div>');

    measureHost.innerHTML = '';
    return out.length ? out : ['<div class="pg-col"><p>&nbsp;</p></div>'];
  }

  /* ── lembar ── */
  var ORNAMENT = '<svg class="fb-ornament" viewBox="0 0 70 20" aria-hidden="true">'
    + '<path d="M6 14 C 22 17, 48 16, 64 8" stroke="currentColor" stroke-width="1.1" fill="none" opacity=".55"/>'
    + '<circle cx="35" cy="13" r="2" fill="currentColor" opacity=".45"/></svg>'
    + '<i class="fb-gloss" aria-hidden="true"></i>';

  function face(pgHtml, sideClass) {
    var f = document.createElement('div');
    var blank = pgHtml == null;
    f.className = 'fb-face ' + sideClass + (blank ? ' fb-blank' : '');
    f.innerHTML = blank ? '' :
      '<div class="fb-page-wrap"><div class="pg-flow">' + pgHtml + '</div>' + ORNAMENT + '</div>';
    return f;
  }

  function zIndexOf(s) { return s < cur ? s + 1 : (sheets.length - s); }

  function apply(instant) {
    if (instant) bookEl.classList.add('no-anim');
    sheets.forEach(function (sh, k) {
      sh.classList.toggle('is-flipped', k < cur);
      if (sh !== animEl) sh.style.zIndex = String(zIndexOf(k));
    });
    if (instant) { void bookEl.offsetWidth; bookEl.classList.remove('no-anim'); }
  }

  function buildSheets() {
    bookEl.classList.add('no-anim');
    bookEl.innerHTML = '';
    sheets = [];
    var T = Math.ceil(pages.length / 2);
    for (var k = 0; k < T; k++) {
      var sh = document.createElement('div');
      sh.className = 'fb-sheet';
      sh.appendChild(face(pages[2 * k], 'fb-front'));
      sh.appendChild(face(pages[2 * k + 1] != null ? pages[2 * k + 1] : null, 'fb-back'));
      sh.addEventListener('transitionend', function (ev) {
        var el = ev.currentTarget;
        if (el === animEl) animEl = null;
        for (var j = 0; j < sheets.length; j++) {
          if (sheets[j] === el) { el.style.zIndex = String(zIndexOf(j)); break; }
        }
      });
      bookEl.appendChild(sh);
      sheets.push(sh);
    }
    cur = 0;
    apply(true);
    void bookEl.offsetWidth;
    bookEl.classList.remove('no-anim');
  }

  /* ── navigasi ── */
  function update() {
    if (!pages.length) return;
    pageIdx = cur === 0 ? 0 : clamp(Math.min(2 * cur - 1, pages.length - 1), 0, pages.length - 1);
    $('fb-chapter').textContent = chapters[chIdx].title || ('Bagian ' + (chIdx + 1));
    $('fb-page-no').textContent = 'hlm ' + (pageIdx + 1) + '/' + pages.length +
      ' · Bab ' + (chIdx + 1) + '/' + chapters.length;
    prevBtn.disabled = cur === 0 && chIdx === 0;
    nextBtn.disabled = cur >= sheets.length && chIdx >= chapters.length - 1;
    if (bar) bar.style.width = overallPct() + '%';
    saveSoon();
  }

  function next() {
    if (cur < sheets.length) {
      animEl = sheets[cur];
      animEl.style.zIndex = String(sheets.length + 5);
      cur++;
      apply(false);
      setTimeout(update, 300);
    } else if (chIdx < chapters.length - 1) {
      loadChapter(chIdx + 1, {});
    }
  }

  function prev() {
    if (cur > 0) {
      cur--;
      animEl = sheets[cur];
      animEl.style.zIndex = String(sheets.length + 5);
      apply(false);
      setTimeout(update, 260);
    } else if (chIdx > 0) {
      loadChapter(chIdx - 1, { startAtEnd: true });
    }
  }

  function goToPage(p) {
    p = clamp(p, 0, pages.length - 1);
    cur = Math.floor((p + 1) / 2); // p genap: depan lembar p/2 ; p ganjil: belakang lembar (p-1)/2 → cur=(p+1)/2
    cur = clamp(cur, 0, sheets.length);
    apply(true);
    update();
  }

  var loading = false;
  function loadChapter(idx, opts) {
    opts = opts || {};
    if (loading) return;
    loading = true;
    chIdx = clamp(idx, 0, chapters.length - 1);
    cur = 0; animEl = null; pageIdx = 0;

    computeMetrics();
    bookEl.innerHTML = '';
    wrapEl.hidden = true;

    setTimeout(function () {
      pages = paginate(chapters[chIdx].html);
      buildSheets();
      wrapEl.hidden = false;

      if (opts.startAtEnd) goToPage(pages.length - 1);
      else if (opts.resumePage) goToPage(opts.resumePage);
      else goToPage(0);

      renderToc();
      loading = false;
    }, 30);
  }

  /* ── kontrol ── */
  nextBtn.addEventListener('click', next);
  prevBtn.addEventListener('click', prev);

  var swipeAt = 0;
  stage.addEventListener('click', function (ev) {
    if (Date.now() - swipeAt < 600) return;
    if (ev.target.closest('.fb-corner')) return;
    var r = stage.getBoundingClientRect();
    var fx = (ev.clientX - r.left) / Math.max(1, r.width);
    if (fx > 0.62) next(); else if (fx < 0.38) prev();
  });

  var downX = null;
  stage.addEventListener('pointerdown', function (ev) { downX = ev.clientX; });
  stage.addEventListener('pointerup', function (ev) {
    if (downX === null) return;
    var dx = ev.clientX - downX;
    downX = null;
    if (Math.abs(dx) > 42) { swipeAt = Date.now(); dx < 0 ? next() : prev(); }
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.target.closest('input, textarea, select')) return;
    if (ev.key === 'ArrowRight') next();
    else if (ev.key === 'ArrowLeft') prev();
  });

  /* ── daftar isi ── */
  var tocPanel = document.getElementById('toc-panel');
  var tocBtn = document.getElementById('rd-toc-btn');

  function openPanel() {
    tocPanel.hidden = false;
    requestAnimationFrame(function () { tocPanel.classList.add('is-open'); });
    tocBtn.setAttribute('aria-expanded', 'true');
  }
  function closePanel() {
    tocPanel.classList.remove('is-open');
    setTimeout(function () { tocPanel.hidden = true; }, 280);
    tocBtn.setAttribute('aria-expanded', 'false');
  }
  tocBtn.addEventListener('click', function () {
    tocPanel.classList.contains('is-open') ? closePanel() : openPanel();
  });
  document.getElementById('toc-close').addEventListener('click', closePanel);

  function renderToc() {
    var list = document.getElementById('toc-list');
    list.innerHTML = '';
    chapters.forEach(function (ch, i) {
      var li = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = ch.title || ('Bagian ' + (i + 1));
      if (i === chIdx) btn.className = 'is-current';
      btn.addEventListener('click', function () { closePanel(); loadChapter(i, {}); });
      li.appendChild(btn);
      list.appendChild(li);
    });
  }

  /* ── tema baca & ukuran huruf ── */
  var THEMES = ['light', 'sepia', 'dark'];
  document.getElementById('rd-theme-btn').addEventListener('click', function () {
    var now = document.documentElement.dataset.readerTheme || 'light';
    document.documentElement.dataset.readerTheme = THEMES[(THEMES.indexOf(now) + 1) % THEMES.length];
  });

  var fsWrap = document.createElement('span');
  fsWrap.className = 'rd-fontsize';
  fsWrap.innerHTML =
    '<button type="button" class="icon-btn rd-fs" id="fs-down" aria-label="Perkecil huruf">A−</button>' +
    '<button type="button" class="icon-btn rd-fs" id="fs-up" aria-label="Perbesar huruf">A+</button>';
  shell.querySelector('.reader-topbar').insertBefore(fsWrap, tocBtn);
  fsWrap.addEventListener('click', function (ev) {
    if (ev.target.id === 'fs-up') fontScale = clamp(fontScale + 0.12, 0.8, 1.7);
    if (ev.target.id === 'fs-down') fontScale = clamp(fontScale - 0.12, 0.8, 1.7);
    document.documentElement.style.setProperty('--rd-scale', String(fontScale));
    loadChapter(chIdx, { resumePage: pageIdx });
  });

  /* ── resize: repaginasi dengan posisi terjaga ── */
  var rz = null;
  window.addEventListener('resize', function () {
    clearTimeout(rz);
    rz = setTimeout(function () { loadChapter(chIdx, { resumePage: pageIdx }); }, 350);
  });

  /* ── boot ── */
  var initSkel = document.getElementById('fb-skeleton');
  var savedPos = loadPos();
  var toastMsg = savedPos && (savedPos.c > 0 || savedPos.p > 2)
    ? 'Lanjut dari posisi terakhirmu.' : null;

  loadChapter(savedPos ? savedPos.c : 0, { resumePage: savedPos ? savedPos.p : 0 });
  if (initSkel) initSkel.remove();
  if (toastMsg && window.VX) VX.toast(toastMsg);
})();
