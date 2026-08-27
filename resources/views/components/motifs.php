<?php
/** Brutalist manga-motif SVGs — used sparingly as intentional design elements. */

/** Speed lines / ink burst. */
function motif_speedlines(int $w = 120, int $h = 40): string
{
    $lines = '';
    for ($i = 0; $i < 14; $i++) {
        $x = 4 + $i * 8;
        $lines .= '<line x1="' . $x . '" y1="2" x2="' . ($x - 2) . '" y2="' . $h . '" stroke="currentColor" stroke-width="2"/>';
    }
    return '<svg class="motif" width="' . $w . '" height="' . $h . '" viewBox="0 0 120 ' . $h . '" fill="none" aria-hidden="true">' . $lines . '</svg>';
}

/** Halftone dot field. */
function motif_halftone(int $w = 120, int $h = 40): string
{
    $dots = '';
    for ($y = 6; $y < $h; $y += 10) {
        for ($x = 6; $x < $w; $x += 10) {
            $dots .= '<circle cx="' . $x . '" cy="' . $y . '" r="2.4" fill="currentColor"/>';
        }
    }
    return '<svg class="motif" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true">' . $dots . '</svg>';
}

/** Manga panel frame with an X. */
function motif_panel(int $s = 64): string
{
    return '<svg class="motif" width="' . $s . '" height="' . $s . '" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <rect x="3" y="3" width="58" height="58" stroke="currentColor" stroke-width="3"/>
      <path d="M16 16 L48 48 M48 16 L16 48" style="stroke:var(--accent)" stroke-width="5"/>
    </svg>';
}
