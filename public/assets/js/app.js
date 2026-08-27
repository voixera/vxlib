/* VoiXLib global behaviors: theme, prefs cache, toasts, reveal, parallax, header, shortcuts. */
(function () {
  'use strict';

  var root = document.documentElement;
  var PREFS_KEY = 'voixlib:prefs';

  // ── Preferences store ────────────────────────────────────────
  function loadPrefs() {
    try { return JSON.parse(document.body.dataset.prefs || '{}') || {}; }
    catch (e) { return {}; }
  }
  var serverPrefs = loadPrefs();
  function localPrefs() {
    try { return JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') || {}; }
    catch (e) { return {}; }
  }
  function savePrefs(patch) {
    var next = Object.assign({}, localPrefs(), patch);
    localStorage.setItem(PREFS_KEY, JSON.stringify(next));
    return next;
  }

  window.VX = {
    csrf: (window.VOIXLIB && window.VOIXLIB.csrf) || '',
    authed: !!(window.VOIXLIB && window.VOIXLIB.authed),
    base: (window.VOIXLIB && window.VOIXLIB.base) || '',
    getPrefs: localPrefs,
    savePrefs: savePrefs,

    api: function (url, options) {
      options = options || {};
      options.headers = Object.assign({
        'Content-Type': 'application/json',
        'X-CSRF-Token': VX.csrf
      }, options.headers || {});
      if (options.body && typeof options.body !== 'string') {
        options.body = JSON.stringify(options.body);
      }
      return fetch(url, options).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (!res.ok) throw new Error(data.error || ('http_' + res.status));
          return data;
        });
      });
    },

    toast: function (message, kind) {
      var host = document.getElementById('toast-root');
      if (!host) return;
      var el = document.createElement('div');
      el.className = 'toast' + (kind === 'error' ? ' toast-error' : '');
      el.textContent = message;
      host.appendChild(el);
      setTimeout(function () {
        el.classList.add('leaving');
        el.addEventListener('animationend', function () { el.remove(); }, { once: true });
      }, 2600);
    }
  };

  // ── Theme toggle ─────────────────────────────────────────────
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-theme-toggle]');
    if (!btn) return;
    var currentDark = root.dataset.theme === 'dark';
    var next = currentDark ? 'light' : 'dark';
    root.dataset.theme = next;
    savePrefs({ theme: next });
    syncSettingsUI(next);
  });

  function applyTheme() {
    var p = localPrefs();
    if (!p.theme && serverPrefs.theme) p.theme = serverPrefs.theme;
    if (p.theme === 'dark') root.dataset.theme = 'dark';
    else if (p.theme === 'light') root.dataset.theme = 'light';
  }
  function syncSettingsUI(theme) {
    var input = document.querySelector('#settings-form input[name="theme"][value="' + theme + '"]');
    if (input) input.checked = true;
  }

  // ── Header shadow on scroll ──────────────────────────────────
  var header = document.getElementById('site-header');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // ── "/" focuses search ───────────────────────────────────────
  document.addEventListener('keydown', function (ev) {
    if (ev.key !== '/' || ev.target.closest('input, textarea, select')) return;
    var q = document.getElementById('header-q');
    if (q) { ev.preventDefault(); q.focus(); q.select(); }
  });

  // ── Reveal on scroll ─────────────────────────────────────────
  var reduced = root.dataset.motion === 'reduced' ||
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if ('IntersectionObserver' in window && !reduced) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
  }

  // ── Hero parallax (pointer, desktop, motion allowed only) ────
  var scene = document.querySelector('[data-parallax-scene]');
  if (scene && matchMedia('(pointer:fine)').matches && !reduced) {
    var layers = scene.querySelectorAll('.layer');
    var covers = document.querySelector('[data-parallax-covers]');
    var raf = null;
    window.addEventListener('mousemove', function (ev) {
      if (raf) return;
      raf = requestAnimationFrame(function () {
        var nx = ev.clientX / window.innerWidth - 0.5;
        var ny = ev.clientY / window.innerHeight - 0.5;
        layers.forEach(function (layer) {
          var depth = parseFloat(layer.dataset.depth || '1');
          layer.style.transform = 'translate(' + (-nx * depth * 7) + 'px,' + (-ny * depth * 4) + 'px)';
        });
        if (covers) covers.style.transform = 'translate(' + (nx * -10) + 'px,' + (ny * -6) + 'px)';
        raf = null;
      });
    }, { passive: true });
  }

  // ── Anonymous→account one-time sync after login ──────────────
  function maybeSync() {
    if (!VX.authed) return;
    var flag = sessionStorage.getItem('voixlib:synced');
    var pending = JSON.parse(localStorage.getItem('voixlib:pendingSync') || 'null');
    if (flag === '1' && !pending) return;

    var payload = { progress: [], bookmarks: [], prefs: {} };
    try {
      payload.progress = JSON.parse(localStorage.getItem('voixlib:progress') || '[]');
      payload.bookmarks = JSON.parse(localStorage.getItem('voixlib:bookmarks') || '[]');
      payload.prefs = localPrefs();
    } catch (e) { /* malformed locals are ignored */ }

    if (!payload.progress.length && !payload.bookmarks.length && !Object.keys(payload.prefs).length) {
      sessionStorage.setItem('voixlib:synced', '1');
      return;
    }

    VX.api('/api/sync.php', { method: 'POST', body: payload })
      .then(function (res) {
        sessionStorage.setItem('voixlib:synced', '1');
        localStorage.removeItem('voixlib:pendingSync');
        localStorage.removeItem('voixlib:progress');
        localStorage.removeItem('voixlib:bookmarks');
        if (res.merged && (res.merged.progress + res.merged.bookmarks) > 0) {
          VX.toast('Synced your local reading data to your account.');
        }
      })
      .catch(function () { /* retry on next visit while flag stays unset */ });
  }
  maybeSync();

  // Expose for page scripts that need the server-side pref baseline.
  VX.serverPrefs = serverPrefs;

  applyTheme();

  // ── Remove boot overlay once the page is interactive ──────────
  var boot = document.getElementById('boot');
  function killBoot() {
    if (!boot) return;
    boot.style.display = 'none';
    boot.remove();
    boot = null;
  }
  if (boot) {
    if (document.readyState === 'complete') setTimeout(killBoot, 220);
    else window.addEventListener('load', function () { setTimeout(killBoot, 220); });
    setTimeout(killBoot, 2600); // hard cap
  }
})();
