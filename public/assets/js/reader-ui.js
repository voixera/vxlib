/* VoiXLib reader UI — modes (scroll / comic), auto-scroll, progress, keyboard.
   Works on the shared `.reader` scope used by both AniList and WeebCentral readers. */
(function () {
  'use strict';

  var VXReader = {
    root: null,
    pages: [],
    mode: 'scroll',
    comicIndex: 0,
    speed: 'off',
    paused: false,
    rafId: null,
    bookId: null, chapter: null, location: null,

    SPEEDS: { off: 0, slow: 0.6, normal: 1.1, fast: 1.9 },
    reduced: false,

    init: function (opts) {
      this.root = document.querySelector('.reader');
      if (!this.root) return;
      this.pages = Array.prototype.slice.call(this.root.querySelectorAll('.reader-page'));
      this.bookId = opts && opts.bookId;
      this.chapter = opts && opts.chapter;
      this.location = opts && opts.location;
      this.reduced = document.documentElement.dataset.motion === 'reduced' ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      this.bindProgress();
      this.bindModes();
      this.bindAutoscroll();
      this.bindKeyboard();
      this.bindSave();
      this.bindBookmark();
      this.applyMode();
    },

    bindBookmark: function () {
      var btn = this.root.querySelector('.action-bookmark');
      if (!btn) return;
      var self = this;
      btn.addEventListener('click', function () {
        var id = parseInt(btn.dataset.bookId, 10);
        if (!id) return;
        var saved = btn.getAttribute('aria-pressed') === 'true';
        if (!saved) {
          self.api('/api/bookmark.php', { action: 'add', book_id: id, location: 'reader' })
            .then(function () {
              btn.setAttribute('aria-pressed', 'true');
              btn.classList.add('is-saved');
              btn.innerHTML = '<svg class="icon icon-bookmark-filled" width="18" height="18" viewBox="0 0 24 24"><path d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="currentColor" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
              if (window.VX) VX.toast('Disimpan ke bookmark.');
            })
            .catch(function () { if (window.VX) VX.toast('Bookmark gagal.', 'error'); });
        } else {
          self.api('/api/bookmark.php', { action: 'list', book_id: id })
            .then(function (res) {
              var marks = (res.bookmarks || []).filter(function (m) { return m.location === 'reader'; });
              return Promise.all(marks.map(function (m) {
                return self.api('/api/bookmark.php', { action: 'remove', bookmark_id: m.id });
              }));
            })
            .then(function () {
              btn.setAttribute('aria-pressed', 'false');
              btn.classList.remove('is-saved');
              btn.innerHTML = '<svg class="icon icon-bookmark" width="18" height="18" viewBox="0 0 24 24"><path d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
              if (window.VX) VX.toast('Bookmark dihapus.');
            })
            .catch(function () { if (window.VX) VX.toast('Bookmark gagal.', 'error'); });
        }
      });
    },

    api: function (url, body) {
      return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.VOIXLIB && window.VOIXLIB.csrf) || '' },
        body: JSON.stringify(body)
      }).then(function (r) { return r.json().catch(function () { return {}; }); });
    },

    bindProgress: function () {
      var bar = this.root.querySelector('#read-progress');
      if (!bar) return;
      this.bar = bar;
      var self = this;
      var update = function () {
        var h = document.documentElement.scrollHeight - window.innerHeight;
        var pct = h > 0 ? (window.scrollY / h) * 100 : 0;
        self.bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
      };
      window.addEventListener('scroll', update, { passive: true });
      window.addEventListener('resize', update);
      update();
    },

    bindModes: function () {
      var self = this;
      var seg = this.root.querySelectorAll('.reader-seg button[data-mode]');
      seg.forEach(function (btn) {
        btn.addEventListener('click', function () {
          self.mode = btn.dataset.mode;
          seg.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
          self.applyMode();
        });
      });
    },

    applyMode: function () {
      var comic = this.mode === 'comic';
      this.root.classList.toggle('mode-comic', comic);
      var autoGroup = this.root.querySelector('#autoscroll-group');
      if (autoGroup) autoGroup.style.display = comic ? 'none' : '';
      if (comic) {
        if (this.comicIndex >= this.pages.length) this.comicIndex = this.pages.length - 1;
        if (this.comicIndex < 0) this.comicIndex = 0;
        this.showPage(this.comicIndex);
        window.scrollTo(0, 0);
      } else {
        this.pages.forEach(function (p) { p.classList.remove('is-current'); });
      }
    },

    showPage: function (i) {
      if (!this.pages.length) return;
      i = Math.max(0, Math.min(i, this.pages.length - 1));
      this.comicIndex = i;
      this.pages.forEach(function (p, idx) { p.classList.toggle('is-current', idx === i); });
      var ind = this.root.querySelector('.reader-chapter-indicator');
      if (ind) ind.innerHTML = '<span>Halaman ' + (i + 1) + ' / ' + this.pages.length + '</span>';
      this.stopAuto();
    },

    bindAutoscroll: function () {
      var self = this;
      var seg = this.root.querySelectorAll('#autoscroll-group .reader-seg button[data-speed]');
      seg.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var sp = btn.dataset.speed;
          if (sp === 'off') { self.setSpeed('off'); return; }
          if (self.speed === sp && !self.paused) { self.paused = true; btn.classList.remove('is-on'); }
          else { self.setSpeed(sp); self.paused = false; btn.classList.add('is-on'); }
        });
      });
    },

    setSpeed: function (sp) {
      this.speed = sp;
      var self = this;
      var seg = this.root.querySelectorAll('#autoscroll-group .reader-seg button[data-speed]');
      seg.forEach(function (b) {
        var on = b.dataset.speed === sp;
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
        b.classList.toggle('is-on', on && sp !== 'off' && !self.paused);
      });
      if (sp === 'off') { this.paused = false; this.stopAuto(); return; }
      this.paused = false;
      this.startAuto();
    },

    startAuto: function () {
      if (this.reduced) return;
      if (this.rafId) return;
      var self = this;
      var step = function () {
        if (self.speed === 'off' || self.paused || self.mode === 'comic') { self.rafId = null; return; }
        window.scrollBy(0, self.SPEEDS[self.speed] || 0);
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
          self.setSpeed('off'); self.rafId = null; return;
        }
        self.rafId = requestAnimationFrame(step);
      };
      this.rafId = requestAnimationFrame(step);
    },

    stopAuto: function () {
      if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; }
    },

    pauseIfRunning: function () {
      if (this.speed !== 'off' && !this.paused) {
        this.paused = true;
        var seg = this.root.querySelectorAll('#autoscroll-group .reader-seg button[data-speed]');
        seg.forEach(function (b) { if (b.dataset.speed === 'off') return; b.classList.remove('is-on'); });
      }
    },

    bindKeyboard: function () {
      var self = this;
      document.addEventListener('keydown', function (e) {
        if (e.target && /^(INPUT|SELECT|TEXTAREA)$/.test(e.target.tagName)) return;
        if (self.mode === 'comic') {
          if (e.key === 'ArrowRight') { e.preventDefault(); self.nextPage(); }
          else if (e.key === 'ArrowLeft') { e.preventDefault(); self.prevPage(); }
        } else {
          if (e.key === 'ArrowRight') { var nx = document.getElementById('ch-next'); if (nx) nx.click(); }
          else if (e.key === 'ArrowLeft') { var pv = document.getElementById('ch-prev'); if (pv) pv.click(); }
        }
      });
      var pages = this.root.querySelector('#pages-container');
      if (pages) {
        pages.addEventListener('click', function (e) {
          if (self.mode !== 'comic') return;
          var r = pages.getBoundingClientRect();
          var fx = (e.clientX - r.left) / Math.max(1, r.width);
          if (fx > 0.6) self.nextPage(); else if (fx < 0.4) self.prevPage();
        });
      }
      ['wheel', 'touchstart', 'pointerdown'].forEach(function (evt) {
        window.addEventListener(evt, function () { self.pauseIfRunning(); }, { passive: true });
      });
    },

    nextPage: function () {
      if (this.comicIndex < this.pages.length - 1) { this.showPage(this.comicIndex + 1); }
      else { var nx = document.getElementById('ch-next'); if (nx) nx.click(); }
    },
    prevPage: function () {
      if (this.comicIndex > 0) { this.showPage(this.comicIndex - 1); }
      else { var pv = document.getElementById('ch-prev'); if (pv) pv.click(); }
    },

    bindSave: function () {
      if (!this.bookId || !this.location) return;
      var self = this;
      var t = null;
      window.addEventListener('scroll', function () {
        if (self.mode === 'comic') return;
        clearTimeout(t);
        t = setTimeout(function () {
          var h = document.documentElement.scrollHeight - window.innerHeight;
          var pct = h > 0 ? Math.round((window.scrollY / h) * 100) : 0;
          fetch('/api/progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: self.bookId, progress: pct, chapter: self.chapter, location: self.location })
          }).catch(function () {});
        }, 2000);
      }, { passive: true });
    }
  };

  window.VXReader = VXReader;
})();
