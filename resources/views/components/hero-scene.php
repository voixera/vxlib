<?php
/**
 * Hero landscape — layered line-art SVG scenery.
 * Layers animate independently (CSS) + parallax (JS, desktop only).
 * Palette is drawn from theme tokens so it adapts to dark mode.
 */
?>
<div class="hero-scene" aria-hidden="true" data-parallax-scene>
  <svg viewBox="0 0 640 560" class="scene-svg" preserveAspectRatio="xMidYMax slice">
    <!-- sky wash -->
    <defs>
      <radialGradient id="vx-sky" cx="62%" cy="24%" r="80%">
        <stop offset="0%" stop-color="var(--scene-sun-halo)" />
        <stop offset="55%" stop-color="var(--scene-sky-2)" />
        <stop offset="100%" stop-color="var(--scene-sky-1)" />
      </radialGradient>
      <linearGradient id="vx-fg" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="var(--scene-fg-1)"/>
        <stop offset="100%" stop-color="var(--scene-fg-2)"/>
      </linearGradient>
    </defs>
    <rect width="640" height="560" fill="url(#vx-sky)"/>

    <g class="layer" data-depth="0.4">
      <!-- stars -->
      <g class="stars">
        <?php foreach ([[70,70],[150,42],[240,90],[330,50],[420,84],[500,44],[575,96],[110,130],[300,140],[520,140],[190,180],[450,190]] as $i => [$sx,$sy]): ?>
          <circle class="star star-<?= $i % 3 ?>" cx="<?= $sx ?>" cy="<?= $sy ?>" r="<?= 1.1 + ($i % 3) * 0.5 ?>"/>
        <?php endforeach; ?>
      </g>
      <!-- moon with halo ring -->
      <g class="moon">
        <circle cx="486" cy="112" r="46" fill="none" stroke="var(--scene-line)" stroke-width="1"/>
        <circle cx="486" cy="112" r="34" fill="var(--scene-moon)"/>
        <circle cx="476" cy="102" r="7" fill="var(--scene-moon-shade)"/>
        <circle cx="498" cy="122" r="4.5" fill="var(--scene-moon-shade)"/>
        <circle cx="492" cy="98" r="3" fill="var(--scene-moon-shade)"/>
      </g>
    </g>

    <g class="layer" data-depth="0.9">
      <!-- drifting clouds: long slow traverse -->
      <g class="cloud cloud-a">
        <path d="M40 210q14-26 44-22t38 12h56q16 0 16 12t-16 12H52q-20 0-12-14z" fill="var(--scene-cloud)"/>
      </g>
      <g class="cloud cloud-b">
        <path d="M300 160q10-18 30-15t26 8h38q11 0 11 8t-11 8h-88q-13 0-6-9z" fill="var(--scene-cloud)" opacity=".8"/>
      </g>
      <g class="cloud cloud-c">
        <path d="M430 250q12-22 36-19t32 10h46q13 0 13 10t-13 10H438q-17 0-8-11z" fill="var(--scene-cloud)" opacity=".65"/>
      </g>
    </g>

    <!-- far ridge -->
    <g class="layer" data-depth="1.4">
      <path d="M0 340 L90 258 L160 320 L235 238 L310 322 L380 270 L452 332 L528 260 L600 330 L640 296 V560 H0 Z"
            fill="var(--scene-far)"/>
      <path d="M0 340 L90 258 L160 320 L235 238 L310 322 L380 270 L452 332 L528 260 L600 330 L640 296"
            fill="none" stroke="var(--scene-line)" stroke-width="1.4"/>
    </g>

    <!-- mid ridge with trees -->
    <g class="layer" data-depth="2">
      <path d="M0 420 L120 350 L230 408 L330 352 L440 416 L540 360 L640 412 V560 H0 Z" fill="var(--scene-mid)"/>
      <path d="M0 420 L120 350 L230 408 L330 352 L440 416 L540 360 L640 412" fill="none" stroke="var(--scene-line)" stroke-width="1.3"/>

      <g fill="none" stroke="var(--scene-line)" stroke-width="1.6" stroke-linecap="round">
        <g class="tree"><path d="M150 372v-26m0 0-9 12m9-12 9 12m-9-20-11 14m11-14 11 14"/><circle cx="150" cy="332" r="1.6" fill="var(--accent)" stroke="none"/></g>
        <g class="tree tree-d"><path d="M356 380v-30m0 0-10 13m10-13 10 13m-10-21-12 15m12-15 12 15"/><circle cx="356" cy="336" r="1.6" fill="var(--accent)" stroke="none"/></g>
        <g class="tree"><path d="M560 392v-24m0 0-8 11m8-11 8 11"/></g>
      </g>
    </g>

    <!-- foreground terrain: the shelf-hill where books sit -->
    <g class="layer" data-depth="2.8">
      <path d="M0 480 Q 160 428 320 466 T 640 458 V560 H0 Z" fill="url(#vx-fg)"/>
      <path d="M0 480 Q 160 428 320 466 T 640 458" fill="none" stroke="var(--scene-line)" stroke-width="1.5"/>

      <!-- three leaning book spines on the hill -->
      <g class="hill-books">
        <g transform="translate(214,414) rotate(-8)">
          <rect width="34" height="86" rx="3" fill="var(--accent)"/>
          <line x1="7" y1="10" x2="27" y2="10" stroke="var(--paper,#F4F1EA)" stroke-width="2" opacity=".85"/>
          <line x1="7" y1="76" x2="27" y2="76" stroke="var(--paper,#F4F1EA)" stroke-width="2" opacity=".55"/>
        </g>
        <g transform="translate(258,404)">
          <rect width="30" height="94" rx="3" fill="var(--ink-strong,#171717)" opacity=".82"/>
          <rect x="5" y="12" width="20" height="3" fill="#F4F1EA" opacity=".8"/>
        </g>
        <g transform="translate(296,416) rotate(6)">
          <rect width="32" height="82" rx="3" fill="none" stroke="var(--scene-line)" stroke-width="2"/>
          <circle cx="16" cy="30" r="9" fill="none" stroke="var(--scene-line)" stroke-width="2"/>
        </g>
        <!-- open book resting beside them -->
        <g transform="translate(352,470)">
          <path d="M0 8 C 16 0, 34 0, 46 8 V 34 C 34 26, 16 26, 0 34 Z" fill="var(--surface,#FFFFFF)" stroke="var(--scene-line)" stroke-width="1.6" stroke-linejoin="round"/>
          <path d="M46 8 C 62 0, 78 0, 92 8 V 34 C 78 26, 62 26, 46 34 Z" fill="var(--surface,#FFFFFF)" stroke="var(--scene-line)" stroke-width="1.6" stroke-linejoin="round"/>
          <path d="M8 12 c 10 -4 20 -4 30 0 m -30 8 c 10 -4 20 -4 30 0 M54 12 c 10 -4 20 -4 30 0 m -30 8 c 10 -4 20 -4 30 0"
                stroke="var(--scene-line)" stroke-width="1.2" fill="none" opacity=".7"/>
          <path d="M46 8 V 34" stroke="var(--scene-line)" stroke-width="1.4"/>
        </g>
      </g>

      <!-- fireflies / particles -->
      <g class="particles">
        <?php foreach ([[80,470],[170,500],[300,520],[420,498],[540,512],[610,480],[240,460]] as $i => [$px,$py]): ?>
          <circle class="particle p-<?= $i % 3 ?>" cx="<?= $px ?>" cy="<?= $py ?>" r="2"/>
        <?php endforeach; ?>
      </g>
    </g>
  </svg>
</div>
