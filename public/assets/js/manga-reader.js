/* VoiXLib manga reader — vertical scrolling, lazy load, skeletons, errors, nav. */
(function () {
  'use strict';

  var root = document.getElementById('mreader');
  if (!root) return;

  var dataEl = document.getElementById('manga-reader-data');
  var payload = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
  var pages = (payload.current && payload.current.pages) || [];

  var bar = document.getElementById('mreader-bar');
  var select = document.getElementById('chapter-select');
  var fsBtn = document.getElementById('mreader-fs');

  /* ---- Lazy load images (IntersectionObserver) ---- */
  var imgs = Array.prototype.slice.call(root.querySelectorAll('.mreader-img'));
  function load(img) {
    var src = img.getAttribute('data-src');
    if (!src) return;
    img.src = src;
  }
  if ('IntersectionObserver' in window && imgs.length) {
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          var img = e.target.querySelector('img') || e.target;
          if (img && img.classList.contains('mreader-img')) {
            load(img);
            img.addEventListener('load', function () {
              img.parentElement.classList.remove('skeleton-load');
            });
            img.addEventListener('error', function () {
              img.parentElement.classList.remove('skeleton-load');
              img.parentElement.classList.add('page-error');
            });
            obs.unobserve(e.target);
          }
        }
      });
    }, { rootMargin: '400px 0px' });
    root.querySelectorAll('.mreader-page').forEach(function (fig) { io.observe(fig); });
  } else {
    imgs.forEach(function (img) {
      load(img);
      img.addEventListener('load', function () { img.parentElement.classList.remove('skeleton-load'); });
      img.addEventListener('error', function () { img.parentElement.classList.remove('skeleton-load'); img.parentElement.classList.add('page-error'); });
    });
  }

  /* ---- Reading progress bar ---- */
  function updateProgress() {
    var h = document.documentElement.scrollHeight - window.innerHeight;
    var pct = h > 0 ? (window.scrollY / h) * 100 : 0;
    if (bar) bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
  }
  window.addEventListener('scroll', updateProgress, { passive: true });
  window.addEventListener('resize', updateProgress);
  updateProgress();

  /* ---- Chapter selector ---- */
  if (select) {
    select.addEventListener('change', function () {
      if (select.value) window.location.href = select.value;
    });
  }

  /* ---- Fullscreen ---- */
  if (fsBtn) {
    fsBtn.addEventListener('click', function () {
      if (!document.fullscreenElement) {
        (document.documentElement.requestFullscreen || function () {}).call(document.documentElement).catch(function () {});
      } else {
        (document.exitFullscreen || function () {}).call(document).catch(function () {});
      }
    });
  }

  /* ---- Keyboard navigation ---- */
  document.addEventListener('keydown', function (e) {
    if (e.target && /^(INPUT|SELECT|TEXTAREA)$/.test(e.target.tagName)) return;
    if (e.key === 'ArrowLeft' && payload.prev) window.location.href = '/manga/read/' + payload.seriesId + '/' + payload.prev;
    else if (e.key === 'ArrowRight' && payload.next) window.location.href = '/manga/read/' + payload.seriesId + '/' + payload.next;
  });
})();
