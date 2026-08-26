<?php
/* State blok: kosong, error, offline — ilustrasi SVG khas VoiXLib. */

function state_svg(string $kind): string
{
    switch ($kind) {
        case 'empty':
            return '<svg viewBox="0 0 220 140" class="state-art" aria-hidden="true">
              <path d="M20 118h180" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M60 118V96l50-18 50 18v22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
              <path d="M85 100l25-9 25 9" fill="none" stroke="var(--accent)" stroke-width="1.8"/>
              <circle cx="110" cy="52" r="16" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <path d="M104 52h12M110 46v12" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round"/>
            </svg>';
        case 'error':
            return '<svg viewBox="0 0 220 140" class="state-art" aria-hidden="true">
              <path d="M20 118h180" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M70 118 92 62a4 4 0 0 1 7 0l22 56" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
              <path d="M86 96h24" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
              <path d="M98 84v-6" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
              <circle cx="160" cy="48" r="14" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <path d="m155 43 10 10M165 43l-10 10" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round"/>
            </svg>';
        case 'offline':
            return '<svg viewBox="0 0 220 140" class="state-art" aria-hidden="true">
              <path d="M20 118h180" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M40 78c38-30 102-30 140 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-dasharray="4 5"/>
              <path d="M64 94c26-20 66-20 92 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-dasharray="3 5"/>
              <path d="M88 110c13-10 31-10 44 0" fill="none" stroke="currentColor" stroke-width="1.7"/>
              <path d="m150 40 24 24M174 40l-24 24" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
            </svg>';
        case 'search':
            return '<svg viewBox="0 0 220 140" class="state-art" aria-hidden="true">
              <path d="M20 118h180" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <circle cx="96" cy="66" r="34" fill="none" stroke="currentColor" stroke-width="1.8"/>
              <path d="m122 92 26 26" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <path d="M82 58c8-8 22-8 30 0" fill="none" stroke="var(--accent)" stroke-width="1.7" stroke-linecap="round"/>
            </svg>';
    }
    return '';
}

/**
 * Render satu state.
 * $kind: empty|error|offline|search
 */
function render_state(string $kind, string $heading, string $body, ?string $ctaHref = null, string $ctaLabel = ''): void
{
    echo '<div class="state-block reveal" role="' . ($kind === 'error' || $kind === 'offline' ? 'alert' : 'status') . '">';
    echo state_svg($kind);
    echo '<h3 class="state-heading">' . e($heading) . '</h3>';
    echo '<p class="state-body">' . e($body) . '</p>';
    if ($ctaHref !== null) {
        echo '<a class="btn btn-ghost" href="' . e($ctaHref) . '">' . e($ctaLabel ?: 'Kembali ke beranda') . '</a>';
    }
    echo '</div>';
}
