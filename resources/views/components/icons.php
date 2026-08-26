<?php
/** Inline SVG icon set. No emoji anywhere in the product chrome. */

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home'     => '<path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'compass'  => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m15.5 8.5-2 5-5 2 2-5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'library'  => '<path d="M5 4v16M9.5 4v16M14 4l4.5 15.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M3.5 20.5h17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'bookmark' => '<path class="bm-path" d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'bookmark-filled' => '<path class="bm-path" d="M7 4.5h10a.8.8 0 0 1 .8.8v14.2L12 15.6 6.2 19.5V5.3a.8.8 0 0 1 .8-.8z" fill="currentColor" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'search'   => '<circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'settings' => '<circle cx="12" cy="12" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 3.5v2.6M12 17.9v2.6M20.5 12h-2.6M6.1 12H3.5M18 6l-1.8 1.8M7.8 16.2 6 18M18 18l-1.8-1.8M7.8 7.8 6 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'user'     => '<circle cx="12" cy="8.5" r="3.8" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20c1.2-3.6 4-5.4 7.5-5.4s6.3 1.8 7.5 5.4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'arrow-right' => '<path d="M4 12h15M13.5 5.5 20 12l-6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'arrow-left'  => '<path d="M20 12H5M10.5 5.5 4 12l6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'external' => '<path d="M9 5h10v10M19 5 8 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.5 19H5.8A.8.8 0 0 1 5 18.2V8.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'share'    => '<circle cx="6.5" cy="12" r="2.3" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="6" r="2.3" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="18" r="2.3" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m8.6 10.8 6.2-3.6M8.6 13.2l6.2 3.6" stroke="currentColor" stroke-width="1.6"/>',
        'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="4" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="13" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="13" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'list'     => '<path d="M8.5 6H20M8.5 12H20M8.5 18H20M4.2 6h.01M4.2 12h.01M4.2 18h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'filter'   => '<path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'close'    => '<path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'sun'      => '<circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 2.8v2.4M12 18.8v2.4M21.2 12h-2.4M5.2 12H2.8M18.5 5.5l-1.7 1.7M7.2 16.8l-1.7 1.7M18.5 18.5l-1.7-1.7M7.2 7.2 5.5 5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'moon'     => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'book-open'=> '<path d="M12 6.5C10 4.8 7 4.3 4 4.5V19c3-.2 6 .3 8 2 2-1.7 5-2.2 8-2V4.5c-3-.2-6 .3-8 2zM12 6.5V21" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'toc'      => '<path d="M5 6h14M5 10.5h9M5 15h14M5 19.5h9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        'type'     => '<path d="M5 7V5h14v2M12 5v14M9.5 19h5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        'trash'    => '<path d="M5 7h14M10 4.5h4M9.5 7l.6 12h3.8l.6-12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'discord'  => '<path d="M8.6 6.4C7.2 6.7 5.9 7.2 4.7 7.9c-2 3.1-2.6 6.1-2.3 9.1 1.5 1.1 3 1.8 4.4 2.2l.9-1.5c-.5-.2-1-.4-1.5-.7l.4-.3c2.9 1.3 6.1 1.3 9 0l.4.3c-.5.3-1 .5-1.5.7l.9 1.5c1.4-.4 2.9-1.1 4.4-2.2.4-3.5-.6-6.5-2.3-9.1-1.2-.7-2.5-1.2-3.9-1.5l-.5 1a12 12 0 0 0-3.6 0zM9 13.4c-.8 0-1.4-.7-1.4-1.6S8.2 10.3 9 10.3s1.4.7 1.4 1.5-.6 1.6-1.4 1.6zm6 0c-.8 0-1.4-.7-1.4-1.6s.6-1.5 1.4-1.5 1.4.7 1.4 1.5-.6 1.6-1.4 1.6z" fill="currentColor"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 7.5V12l3 2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'globe'    => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.6"/><ellipse cx="12" cy="12" rx="3.8" ry="8.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M3.8 9.5h16.4M3.8 14.5h16.4" stroke="currentColor" stroke-width="1.4"/>',
    ];
    $body = $paths[$name] ?? '';
    return "<svg class=\"icon icon-$name\" width=\"$size\" height=\"$size\" viewBox=\"0 0 24 24\" aria-hidden=\"true\" focusable=\"false\">$body</svg>";
}
