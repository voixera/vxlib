/* Settings page: instant apply + range outputs; form still POSTs for persistence. */
(function () {
  'use strict';

  var form = document.getElementById('settings-form');
  if (!form) return;

  // live outputs
  form.querySelectorAll('input[type="range"]').forEach(function (range) {
    var out = form.querySelector('output[for="' + range.id + '"]') || range.parentElement.querySelector('output');
    if (!out) return;
    var fmt = function () {
      return range.step === '0.1' ? Number(range.value).toFixed(1)
        : range.value + (range.name === 'reader_font' ? 'px' : range.name === 'reader_width' ? 'rem' : '');
    };
    range.addEventListener('input', function () { out.textContent = fmt(); });
  });

  // instant theme/motion feedback before submit
  form.querySelectorAll('input[name="theme"]').forEach(function (r) {
    r.addEventListener('change', function () {
      var next = r.value;
      if (next === 'auto') {
        next = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      document.documentElement.dataset.theme = next;
      VX.savePrefs({ theme: r.value });
    });
  });
  form.querySelectorAll('input[name="motion"]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.documentElement.dataset.motion = r.value === 'reduced' ? 'reduced' : '';
      VX.savePrefs({ motion: r.value });
    });
  });
})();
