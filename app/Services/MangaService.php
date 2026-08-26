<?php

declare(strict_types=1);

/**
 * MangaService — deterministic, original SVG comic pages for VoiXLib-native
 * books (source = voixlib). Every page is generated from the book's
 * external_id + page number, so no art files exist and nothing is scraped.
 */

final class MangaService
{
    private const W = 800;
    private const H = 1200;
    private const INK = '#1B1712';

    private const TITLES = [
        'The Sound of Turning Pages', 'Rain on the School Roof', 'Afterburn',
        'Paper Cranes at Dawn', 'The Long Way Home', 'Signal Fire',
        'Where the Wind Stops', 'Second Serve', 'Half-Light', 'Cassette Summer',
    ];

    private const LINES = [
        'Senpai!! Wait—', 'Not again…', 'Run!!', 'I had to know.', 'One more lap!',
        'It’s already here.', 'Don’t look back.', 'We made it…', 'You knew?',
        'Tomorrow, then.', 'Hold on tight!', 'This is our story.', 'Impossible…',
        'Go without me!', 'Finally.', 'Nice swing.', 'Who’s there?!',
        'Page one starts now.', 'Bought one too many…', 'The 7:15 train.',
    ];

    private const SFX = ['DOOM', 'WHAM', 'WHOOSH', 'KRAK', 'FWOOSH', 'TAP TAP', 'CLANG', 'SHHH', 'BADUMP', 'ZING'];

    private const PALETTES = [
        ['#E8564A', '#FCEEE3', '#2B1D16'],
        ['#3D7BC9', '#EAF2FB', '#141C26'],
        ['#F2A93D', '#FFF6E0', '#241A0C'],
        ['#57A773', '#EDF7EF', '#12211A'],
        ['#8C5FB8', '#F4EFFA', '#191225'],
    ];

    public static function isManga(array $book): bool
    {
        return ($book['source'] ?? '') === 'voixlib';
    }

    public static function pageCount(string $ext): int
    {
        return 22 + (abs(crc32('count|' . $ext)) % 5) * 2; // 22..30, always even
    }

    /** @return array<int,array{index:int,title:string,start_page:int,pages:string[]}> */
    public static function chapters(string $ext): array
    {
        $total = self::pageCount($ext);
        $count = (int)ceil($total / 10);
        $out = [];
        for ($c = 0; $c < $count; $c++) {
            $from = $c * 10 + 1;
            $to = min($total, $from + 9);
            $t = self::TITLES[abs(crc32("title|$ext|$c")) % count(self::TITLES)];
            $pages = [];
            for ($p = $from; $p <= $to; $p++) {
                $pages[] = '/page.php?' . http_build_query(['b' => $ext, 'p' => $p]);
            }
            $out[] = ['index' => $c, 'title' => sprintf('Ch. %d — %s', $c + 1, $t), 'start_page' => $from, 'pages' => $pages];
        }
        return $out;
    }

    public static function pageSvg(string $ext, int $page): string
    {
        $total = self::pageCount($ext);
        if ($page < 1 || $page > $total) {
            throw new InvalidArgumentException('page out of range');
        }

        $R = self::rng($ext . '#' . $page);
        $pick = function (array $a) use ($R) {
            return $a[(int)floor($R() * count($a)) % count($a)];
        };
        [$accent, $paperTone, $deep] = $pick(self::PALETTES);
        $series = htmlspecialchars(mb_substr($ext, strpos($ext, ':', strpos($ext, ':') + 1) + 1) ?: $ext, ENT_XML1, 'UTF-8');

        if ($page === 1) {
            $body = self::titlePage($R, $accent, $deep);
        } else {
            $body = self::comicPage($page, $R, $pick, $accent, $paperTone, $deep);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . self::W . '" height="' . self::H
            . '" viewBox="0 0 ' . self::W . ' ' . self::H . '" role="img" aria-label="Manga page ' . $page . '">'
            . '<defs>'
            . '<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0" stop-color="' . $paperTone . '"/><stop offset="1" stop-color="#FFFFFF"/></linearGradient>'
            . '<pattern id="ht" width="9" height="9" patternUnits="userSpaceOnUse">'
            . '<circle cx="4.5" cy="4.5" r="1.5" fill="' . self::INK . '"/></pattern>'
            . '<pattern id="ht2" width="14" height="14" patternUnits="userSpaceOnUse">'
            . '<circle cx="7" cy="7" r="2.4" fill="' . $accent . '"/></pattern>'
            . '</defs>'
            . '<rect width="' . self::W . '" height="' . self::H . '" fill="#FBF8F1"/>'
            . $body
            . '<text x="36" y="30" font-family="Segoe UI,system-ui,sans-serif" font-size="15" letter-spacing="4"'
            . ' fill="' . self::INK . '" opacity=".5">' . strtoupper($series) . '</text>'
            . '<circle cx="' . (self::W - 44) . '" cy="' . (self::H - 40) . '" r="17" fill="' . $accent . '"/>'
            . '<text x="' . (self::W - 44) . '" y="' . (self::H - 34) . '" text-anchor="middle"'
            . ' font-family="Segoe UI,system-ui,sans-serif" font-weight="700" font-size="17" fill="#FFFFFF">' . $page . '</text>'
            . '</svg>';
    }

    // ── internals ────────────────────────────────────────────────

    private static function rng(string $key): \Closure
    {
        $s = abs(crc32($key)) | 1;
        return function () use (&$s) {
            $s = ($s * 1103515245 + 12345) % 2147483648;
            return $s / 2147483647;
        };
    }

    private static function esc(string $t): string
    {
        return htmlspecialchars($t, ENT_XML1, 'UTF-8');
    }

    /** Page 1: cover-style title splash. */
    private static function titlePage(\Closure $R, string $accent, string $deep): string
    {
        $sfx = '';
        for ($i = 0; $i < 26; $i++) {
            $a = $R() * M_PI * 2;
            $r1 = 150 + $R() * 60;
            $r2 = $r1 + 90 + $R() * 130;
            $cx = 400;
            $cy = 430;
            $sfx .= '<line x1="' . round($cx + cos($a) * $r1) . '" y1="' . round($cy + sin($a) * $r1)
                . '" x2="' . round($cx + cos($a) * $r2) . '" y2="' . round($cy + sin($a) * $r2)
                . '" stroke="' . self::INK . '" stroke-width="' . (1 + $R() * 2.6) . '" opacity=".45"/>';
        }
        $tilt = -6 + $R() * 12;

        return '<g>'
            . '<rect x="30" y="52" width="740" height="1096" fill="none" stroke="' . self::INK . '" stroke-width="4"/>'
            . '<circle cx="400" cy="430" r="230" fill="url(#ht)" opacity=".18"/>'
            . $sfx
            . '<circle cx="400" cy="430" r="120" fill="' . $accent . '"/>'
            . self::figure(400, 520, 1.35, false, 'stand', $deep)
            . '<g transform="rotate(' . number_format($tilt, 1) . ' 400 800)">'
            . '<rect x="70" y="752" width="660" height="96" fill="' . self::INK . '"/>'
            . '<text x="400" y="822" text-anchor="middle" font-family="Segoe UI,system-ui,sans-serif" font-weight="900"'
            . ' font-size="58" fill="#FBF8F1" letter-spacing="2">CHAPTER 1</text></g>'
            . '<rect x="330" y="700" width="140" height="14" fill="' . $accent . '"/>'
            . '</g>';
    }

    /** Pages 2+: panel grid + scenes. */
    private static function comicPage(int $page, \Closure $R, \Closure $pick, string $accent, string $paperTone, string $deep): string
    {
        [$panels, $variant] = self::layout(abs(crc32('lay|' . $page)) % 6);
        $svg = '';
        foreach ($panels as $i => $pn) {
            [$x, $y, $w, $h] = $pn;
            $type = self::SCENES[($i + $page + (int)($R() * 2)) % count(self::SCENES)];
            $clip = 'p' . $page . 'x' . $i;
            $svg .= '<clipPath id="' . $clip . '"><rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="4"/></clipPath>';
            $svg .= '<g clip-path="url(#' . $clip . ')">' . self::$type($x, $y, $w, $h, $R, $accent, $paperTone, $deep) . '</g>';
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="4"'
                . ' fill="none" stroke="' . self::INK . '" stroke-width="3.5"/>';
        }

        // speech bubbles on up to two panels
        $nb = 1 + (int)floor($R() * 2);
        for ($b = 0; $b < $nb; $b++) {
            $pn = $panels[(int)($R() * count($panels)) % count($panels)];
            $bw = min(300, $pn[2] * 0.62);
            $bh = 64 + (int)($R() * 26);
            $bx = $pn[0] + 18 + $R() * max(10, $pn[2] - $bw - 36);
            $by = $pn[1] + 20 + $R() * 30;
            $svg .= self::bubble($bx + $bw / 2, $by + $bh / 2, $bw, $bh, [(string)$pick(self::LINES)], $R);
        }

        // one SFX burst somewhere
        if ($variant !== 5 && $R() > 0.25) {
            $pn = $panels[count($panels) - 1];
            $sx = $pn[0] + $pn[2] * (0.25 + $R() * 0.5);
            $sy = $pn[1] + $pn[3] * (0.3 + $R() * 0.4);
            $svg .= '<text x="' . round($sx) . '" y="' . round($sy) . '" font-family="Segoe UI,system-ui,sans-serif"'
                . ' font-weight="900" font-style="italic" font-size="' . (34 + (int)($R() * 22)) . '"'
                . ' fill="' . $accent . '" stroke="' . self::INK . '" stroke-width="1.6" paint-order="stroke"'
                . ' transform="rotate(' . round(-12 + $R() * 24) . ' ' . round($sx) . ' ' . round($sy) . ')"'
                . '>' . self::esc((string)$pick(self::SFX)) . '</text>';
        }
        return $svg;
    }

    /** @return array{0:list<array{int,int,int,int}>,1:int} */
    private static function layout(int $v): array
    {
        $m = 34;
        $g = 14;
        $x0 = $m;
        $y0 = 48;
        $aw = self::W - $m * 2;
        $ah = self::H - $y0 - $m - 6;
        $hw = (int)(($aw - $g) / 2);

        switch ($v) {
            case 0: // hero top + duo
                $top = (int)($ah * 0.46);
                $bh = $ah - $top - $g;
                return [[[$x0, $y0, $aw, $top], [$x0, $y0 + $top + $g, $hw, $bh], [$x0 + $hw + $g, $y0 + $top + $g, $hw, $bh]], $v];
            case 1: // quartet + wide base
                $q = (int)(($ah - $g * 2) * 0.62 / 2);
                $base = $ah - $q * 2 - $g * 2;
                return [
                    [
                        [$x0, $y0, $hw, $q], [$x0 + $hw + $g, $y0, $hw, $q],
                        [$x0, $y0 + $q + $g, $hw, $q], [$x0 + $hw + $g, $y0 + $q + $g, $hw, $q],
                        [$x0, $y0 + ($q + $g) * 2, $aw, $base],
                    ],
                    $v,
                ];
            case 2: // tall left + right stack + strip
                $lw = (int)($aw * 0.46);
                $rw = $aw - $lw - $g;
                $th = (int)(($ah - $g) / 2);
                $strip = (int)($ah * 0.2);
                $tall = $ah - $strip - $g;
                return [
                    [
                        [$x0, $y0, $lw, $tall],
                        [$x0 + $lw + $g, $y0, $rw, $th],
                        [$x0 + $lw + $g, $y0 + $th + $g, $rw, $tall - $th - $g],
                        [$x0, $y0 + $tall + $g, $aw, $strip],
                    ],
                    $v,
                ];
            case 3: // triptych columns
                $cw = (int)(($aw - $g * 2) / 3);
                $ch2 = (int)($ah * 0.55);
                $ch3 = $ah - $ch2 - $g;
                return [
                    [
                        [$x0, $y0, $cw, $ch2], [$x0 + $cw + $g, $y0, $cw, $ch2], [$x0 + ($cw + $g) * 2, $y0, $cw, $ch2],
                        [$x0, $y0 + $ch2 + $g, $aw, $ch3],
                    ],
                    $v,
                ];
            case 4: // strips sandwich
                $t = (int)($ah * 0.28);
                $mid = $ah - $t * 2 - $g * 2;
                return [
                    [
                        [$x0, $y0, $aw, $t],
                        [$x0, $y0 + $t + $g, $hw, $mid], [$x0 + $hw + $g, $y0 + $t + $g, $hw, $mid],
                        [$x0, $y0 + $t + $mid + $g * 2, $aw, $t],
                    ],
                    $v,
                ];
            default: // splash
                return [[[$x0, $y0, $aw, $ah]], $v];
        }
    }

    // ── scene painters (signature: x,y,w,h,R,accent,paperTone,deep → svg) ──

    private static function sceneSkyline(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="url(#sky)"/>';
        $sunX = $x + (int)($w * (0.2 + $R() * 0.6));
        $sunY = $y + (int)($h * (0.18 + $R() * 0.25));
        $svg .= '<circle cx="' . $sunX . '" cy="' . $sunY . '" r="' . (int)(min($w, $h) / 7) . '" fill="' . $accent . '"/>';
        for ($i = 0; $i < 3; $i++) {
            $cy2 = $y + (int)($h * (0.12 + $R() * 0.3));
            $cx2 = $x + (int)($w * $R());
            $svg .= '<ellipse cx="' . $cx2 . '" cy="' . $cy2 . '" rx="' . (int)(40 + $R() * 50) . '" ry="' . (int)(12 + $R() * 8)
                . '" fill="#FFFFFF" opacity=".8"/>';
        }
        $bx = $x - 20;
        while ($bx < $x + $w + 20) {
            $bw = (int)(40 + $R() * 70);
            $bh = (int)($h * (0.3 + $R() * 0.42));
            $by = $y + $h - $bh;
            $svg .= '<rect x="' . round($bx) . '" y="' . round($by) . '" width="' . $bw . '" height="' . $bh . '" fill="' . $deep . '"/>';
            for ($wy = $by + 12; $wy < $y + $h - 14; $wy += 20) {
                for ($wx = $bx + 8; $wx < $bx + $bw - 10; $wx += 18) {
                    if ($R() > 0.72) {
                        $svg .= '<rect x="' . round($wx) . '" y="' . round($wy) . '" width="7" height="9" fill="' . $accent . '" opacity=".85"/>';
                    }
                }
            }
            $bx += $bw + (int)($R() * 16);
        }
        $svg .= '<line x1="' . $x . '" y1="' . ($y + $h - 3) . '" x2="' . ($x + $w) . '" y2="' . ($y + $h - 3) . '" stroke="' . self::INK . '" stroke-width="4"/>';
        return $svg;
    }

    private static function sceneAction(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $cx = $x + (int)($w * (0.35 + $R() * 0.3));
        $cy = $y + (int)($h * (0.42 + $R() * 0.2));
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="#FDFBF5"/>';
        $maxR = (int)(sqrt($w * $w + $h * $h) / 2) + 40;
        for ($i = 0; $i < 34; $i++) {
            $a = ($i / 34) * M_PI * 2 + $R() * 0.15;
            $r1 = (int)(30 + $R() * 40);
            $svg .= '<line x1="' . round($cx + cos($a) * $r1) . '" y1="' . round($cy + sin($a) * $r1)
                . '" x2="' . round($cx + cos($a) * $maxR) . '" y2="' . round($cy + sin($a) * $maxR)
                . '" stroke="' . self::INK . '" stroke-width="' . (1 + $R() * 2.4) . '" opacity=".5"/>';
        }
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="56" fill="' . $accent . '"/>'
            . self::figure($cx + 30, $cy + (int)($h * 0.22), min($w, $h) / 260, true, 'run', $deep)
            . '<line x1="' . $x . '" y1="' . ($y + (int)($h * 0.82)) . '" x2="' . ($x + $w) . '" y2="' . ($y + (int)($h * 0.80))
            . '" stroke="' . self::INK . '" stroke-width="4"/>';
        return $svg;
    }

    private static function scenePortrait(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $cx = $x + (int)($w / 2);
        $cy = $y + (int)($h * 0.46);
        $hr = (int)(min($w, $h) * 0.23);
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="url(#ht)" opacity=".14"/>';
        $spikes = '';
        for ($i = 0; $i <= 8; $i++) {
            $a = M_PI + ($i / 8) * M_PI;
            $rr = $hr * (1.25 + $R() * 0.45);
            $spikes .= round($cx + cos($a) * $rr) . ',' . round($cy - sin($a) * $rr * 0.92) . ' ';
        }
        $svg .= '<polygon points="' . trim($spikes) . '" fill="' . $deep . '"/>'
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $hr . '" fill="' . $deep . '"/>'
            . '<path d="M' . ($cx - $hr * 0.65) . ' ' . ($cy + $hr * 0.95) . ' Q ' . $cx . ' ' . ($cy + $hr * 1.5)
            . ' ' . ($cx + $hr * 0.65) . ' ' . ($cy + $hr * 0.95) . ' L ' . ($cx + $hr * 0.9) . ' ' . ($y + $h)
            . ' L ' . ($cx - $hr * 0.9) . ' ' . ($y + $h) . ' Z" fill="' . $deep . '"/>'
            . '<path d="M' . ($cx - $hr * 0.5) . ' ' . ($cy + $hr * 0.05) . ' q ' . $hr * 0.28 . ' ' . $hr * 0.22 . ' ' . $hr * 0.56 . ' 0"'
            . ' fill="none" stroke="' . self::INK . '" stroke-width="' . max(3, (int)($hr * 0.09)) . '" stroke-linecap="round"/>'
            . '<path d="M' . ($cx - $hr * 0.52) . ' ' . ($cy - $hr * 0.28) . ' l ' . $hr * 0.3 . ' 6" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>'
            . '<path d="M' . ($cx + $hr * 0.22) . ' ' . ($cy - $hr * 0.28) . ' l ' . $hr * 0.3 . ' 6" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>';
        for ($i = 0; $i < 3; $i++) {
            $ly = $cy + $hr * (0.35 + $i * 0.14);
            $svg .= '<line x1="' . ($cx + $hr * 0.55) . '" y1="' . round($ly) . '" x2="' . ($cx + $hr * 0.95) . '" y2="' . round($ly)
                . '" stroke="' . $accent . '" stroke-width="3" opacity=".6"/>';
        }
        return $svg;
    }

    private static function sceneRain(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="#EFEAE0"/>';
        $wx = $x + (int)($w * 0.14);
        $wy = $y + (int)($h * 0.12);
        $ww = (int)($w * 0.72);
        $wh = (int)($h * 0.6);
        $svg .= '<rect x="' . $wx . '" y="' . $wy . '" width="' . $ww . '" height="' . $wh . '" fill="url(#sky)"/>'
            . '<line x1="' . ($wx + $ww / 2) . '" y1="' . $wy . '" x2="' . ($wx + $ww / 2) . '" y2="' . ($wy + $wh) . '" stroke="' . self::INK . '" stroke-width="5"/>'
            . '<line x1="' . $wx . '" y1="' . ($wy + $wh / 2) . '" x2="' . ($wx + $ww) . '" y2="' . ($wy + $wh / 2) . '" stroke="' . self::INK . '" stroke-width="5"/>';
        $drops = '';
        for ($i = 0; $i < 26; $i++) {
            $dx = $wx + $R() * $ww;
            $dy = $wy + $R() * $wh;
            $drops .= '<line x1="' . round($dx) . '" y1="' . round($dy) . '" x2="' . round($dx - 7) . '" y2="' . round($dy + 16)
                . '" stroke="' . $accent . '" stroke-width="2.4" opacity=".65"/>';
        }
        $svg .= $drops
            . '<rect x="' . $wx . '" y="' . $wy . '" width="' . $ww . '" height="' . $wh . '" fill="none" stroke="' . self::INK . '" stroke-width="6"/>'
            . '<circle cx="' . ($x + (int)($w * 0.86)) . '" cy="' . ($y + (int)($h * 0.68)) . '" r="26" fill="' . $accent . '"/>'
            . self::figure($x + (int)($w * 0.84), $y + (int)($h * 0.88), min($w, $h) / 340, false, 'sit', $deep);
        return $svg;
    }

    private static function sceneCrowd(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="url(#ht2)" opacity=".2"/>';
        $baseY = $y + (int)($h * 0.86);
        $n = 3 + (int)($R() * 2);
        for ($i = 0; $i < $n; $i++) {
            $fx = $x + (int)($w * ((($i + 0.5) / $n) + ($R() - 0.5) * 0.1));
            $sc = 0.8 + $R() * 0.5;
            $svg .= self::figure($fx, $baseY, min($w, $h) / 300 * $sc, $R() > 0.5, $R() > 0.5 ? 'point' : 'stand', $deep);
        }
        $svg .= '<line x1="' . $x . '" y1="' . $baseY . '" x2="' . ($x + $w) . '" y2="' . $baseY . '" stroke="' . self::INK . '" stroke-width="4"/>';
        return $svg;
    }

    private static function sceneImpact(int $x, int $y, int $w, int $h, \Closure $R, string $accent, string $paperTone, string $deep): string
    {
        $cx = $x + (int)($w / 2);
        $cy = $y + (int)($h * 0.5);
        $pts = '';
        $spikes = 12 + (int)($R() * 6);
        for ($i = 0; $i < $spikes * 2; $i++) {
            $a = ($i / ($spikes * 2)) * M_PI * 2;
            $rr = ($i % 2 === 0 ? 1 : 0.45) * min($w, $h) * (0.32 + $R() * 0.1);
            $pts .= round($cx + cos($a) * $rr) . ',' . round($cy + sin($a) * $rr) . ' ';
        }
        $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="#FDFBF5"/>'
            . '<polygon points="' . trim($pts) . '" fill="' . $accent . '" stroke="' . self::INK . '" stroke-width="4"/>'
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . (int)(min($w, $h) * 0.1) . '" fill="none" stroke="' . self::INK . '" stroke-width="3" opacity=".5"/>'
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . (int)(min($w, $h) * 0.16) . '" fill="none" stroke="' . self::INK . '" stroke-width="2" opacity=".3"/>';
        for ($i = 0; $i < 7; $i++) {
            $tx = $x + $R() * $w;
            $ty = $y + $h * (0.75 + $R() * 0.2);
            $ts = 5 + $R() * 9;
            $svg .= '<polygon points="' . round($tx) . ',' . round($ty) . ' ' . round($tx + $ts) . ',' . round($ty - $ts * 1.4)
                . ' ' . round($tx + $ts * 2) . ',' . round($ty) . '" fill="' . $deep . '"/>';
        }
        $svg .= self::figure($cx - (int)($w * 0.06), $y + (int)($h * 0.78), min($w, $h) / 320, false, 'braced', $deep);
        return $svg;
    }

    private const SCENES = ['sceneSkyline', 'sceneAction', 'scenePortrait', 'sceneRain', 'sceneCrowd', 'sceneImpact'];

    /** Stylized silhouette figure. Poses: stand/run/point/sit/braced. */
    private static function figure(int $cx, int $groundY, float $scale, bool $flip, string $pose, string $deep): string
    {
        $u = 60 * $scale; // unit
        $f = $flip ? -1 : 1;
        $head = $groundY - $u * 2.55;
        $g = function (float $v) use ($f) {
            return $v * $f;
        };
        $limb = function (float $x1, float $y1, float $x2, float $y2) use ($u, $deep) {
            return '<line x1="' . round($x1) . '" y1="' . round($y1) . '" x2="' . round($x2) . '" y2="' . round($y2)
                . '" stroke="' . $deep . '" stroke-width="' . round($u * 0.22) . '" stroke-linecap="round"/>';
        };

        $svg = '<g>';
        switch ($pose) {
            case 'run':
                $svg .= $limb($cx, $groundY - $u * 1.4, $cx - $g($u * 0.7), $groundY - $u * 0.1)
                    . $limb($cx, $groundY - $u * 1.4, $cx + $g($u * 0.75), $groundY - $u * 0.55)
                    . $limb($cx, $groundY - $u * 2.1, $cx + $g($u * 0.85), $groundY - $u * 2.6)
                    . $limb($cx, $groundY - $u * 2.1, $cx - $g($u * 0.5), $groundY - $u * 1.2);
                break;
            case 'point':
                $svg .= $limb($cx, $groundY - $u * 2.1, $cx + $g($u * 1.0), $groundY - $u * 2.7)
                    . $limb($cx, $groundY - $u * 2.1, $cx - $g($u * 0.3), $groundY - $u * 1.1);
                break;
            case 'sit':
                $svg .= $limb($cx, $groundY - $u * 1.1, $cx + $g($u * 0.8), $groundY)
                    . $limb($cx, $groundY - $u * 1.6, $cx + $g($u * 0.35), $groundY - $u * 0.9);
                break;
            case 'braced':
                $svg .= $limb($cx, $groundY - $u * 1.4, $cx - $g($u * 0.9), $groundY)
                    . $limb($cx, $groundY - $u * 1.4, $cx + $g($u * 0.9), $groundY)
                    . $limb($cx, $groundY - $u * 2.0, $cx - $g($u * 0.9), $groundY - $u * 2.9)
                    . $limb($cx, $groundY - $u * 2.0, $cx + $g($u * 0.9), $groundY - $u * 2.9);
                break;
            default: // stand
                $svg .= $limb($cx, $groundY - $u * 1.3, $cx - $g($u * 0.3), $groundY)
                    . $limb($cx, $groundY - $u * 1.3, $cx + $g($u * 0.3), $groundY)
                    . $limb($cx, $groundY - $u * 2.0, $cx - $g($u * 0.45), $groundY - $u * 1.1)
                    . $limb($cx, $groundY - $u * 2.0, $cx + $g($u * 0.45), $groundY - $u * 1.1);
        }
        $svg .= '<path d="M' . round($cx - $u * 0.38) . ' ' . round($groundY - $u * 1.35)
            . ' L ' . round($cx - $u * 0.28) . ' ' . round($groundY - $u * 2.35)
            . ' L ' . round($cx + $u * 0.28) . ' ' . round($groundY - $u * 2.35)
            . ' L ' . round($cx + $u * 0.38) . ' ' . round($groundY - $u * 1.35) . ' Z" fill="' . $deep . '"/>'
            . '<circle cx="' . round($cx) . '" cy="' . round($head) . '" r="' . round($u * 0.42) . '" fill="' . $deep . '"/>'
            . '</g>';
        return $svg;
    }

    private static function bubble(float $cx, float $cy, float $w, float $h, array $lines, \Closure $R): string
    {
        $tailX = $cx - $w * 0.2 + $R() * $w * 0.4;
        $tailY = $cy + $h / 2 + 26 + $R() * 10;
        $svg = '<g>'
            . '<polygon points="' . round($cx - $w * 0.08) . ',' . round($cy + $h * 0.32) . ' '
            . round($tailX) . ',' . round($tailY) . ' ' . round($cx + $w * 0.12) . ',' . round($cy + $h * 0.36)
            . '" fill="#FFFFFF" stroke="' . self::INK . '" stroke-width="3"/>'
            . '<ellipse cx="' . round($cx) . '" cy="' . round($cy) . '" rx="' . round($w / 2) . '" ry="' . round($h / 2)
            . '" fill="#FFFFFF" stroke="' . self::INK . '" stroke-width="3.2"/>';
        $n = count($lines);
        foreach ($lines as $i => $line) {
            $ly = $cy + ($i - ($n - 1) / 2) * 24 + 7;
            $svg .= '<text x="' . round($cx) . '" y="' . round($ly) . '" text-anchor="middle"'
                . ' font-family="Segoe UI,system-ui,sans-serif" font-weight="600" font-size="' . min(21, (int)($w / 13))
                . '" fill="' . self::INK . '">' . self::esc($line) . '</text>';
        }
        return $svg . '</g>';
    }
}
