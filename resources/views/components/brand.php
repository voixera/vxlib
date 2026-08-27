<?php
/** Brand SVGs. Inline so they inherit currentColor and need no extra requests. */

/** VoiXLib mark — square ink frame with a crossed X; the right stroke carries the accent. */
function voixlib_mark(int $size = 28): string
{
    return '<svg class="brand-mark" width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" '
        . 'fill="none" aria-hidden="true" focusable="false">'
        . '<rect x="2" y="2" width="28" height="28" stroke="currentColor" stroke-width="3"/>'
        . '<path d="M9 9 L23 23" stroke="currentColor" stroke-width="4.5" stroke-linecap="square"/>'
        . '<path d="M23 9 L9 23" style="stroke:var(--accent)" stroke-width="4.5" stroke-linecap="square"/>'
        . '</svg>';
}

/** Full wordmark: mark + type. The X carries the accent. */
function voixlib_wordmark(bool $mono = false): string
{
    $x = $mono ? '' : ' class="wm-x"';
    return '<span class="wordmark">' . voixlib_mark(26)
        . '<span class="wm-text">Voi<b' . $x . '>X</b>Lib</span></span>';
}
