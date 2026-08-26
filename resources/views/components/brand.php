<?php
/** Brand SVGs. Inline so they inherit currentColor and need no extra requests. */

/** VoiXLib mark: an open-book “X” — two crossing bookmark ribbons on a plate. */
function voixlib_mark(int $size = 28, bool $mono = false): string
{
    $accent = $mono ? 'currentColor' : 'var(--accent)';
    return <<<SVG
<svg class="brand-mark" width="$size" height="$size" viewBox="0 0 32 32" fill="none" aria-hidden="true">
  <rect x="1.25" y="1.25" width="29.5" height="29.5" rx="3" stroke="currentColor" stroke-width="1.6"/>
  <path d="M8 7.5 L15.2 16 L8 24.5 L11 24.5 L16.1 18.4 L21.2 24.5 L24.2 24.5 L17 16 L24.2 7.5 L21.2 7.5 L16.1 13.6 L11 7.5 Z" fill="currentColor" opacity=".92"/>
  <path d="M11 7.5h-3L15.2 16l1.9-2.3z" fill="$accent"/>
</svg>
SVG;
}

/** Full wordmark: mark + type. The X carries the accent. */
function voixlib_wordmark(bool $mono = false): string
{
    $x = $mono ? '' : ' class="wm-x"';
    return '<span class="wordmark">' . voixlib_mark(26, true)
        . '<span class="wm-text">Voi<b' . $x . '>X</b>Lib</span></span>';
}
