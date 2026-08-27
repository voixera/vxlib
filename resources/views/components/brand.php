<?php
/** Brand SVGs. Inline so they inherit currentColor and need no extra requests. */

/** VoiXLib mark: brand logo from Icon.jpeg. */
function voixlib_mark(int $size = 28, bool $mono = false): string
{
    return '<img src="' . asset('Icon.jpeg') . '" alt="VoiXLib" class="brand-mark" width="' . $size . '" height="' . $size . '" loading="lazy" decoding="async">';
}

/** Full wordmark: mark + type. The X carries the accent. */
function voixlib_wordmark(bool $mono = false): string
{
    $x = $mono ? '' : ' class="wm-x"';
    return '<span class="wordmark">' . voixlib_mark(26, true)
        . '<span class="wm-text">Voi<b' . $x . '>X</b>Lib</span></span>';
}
