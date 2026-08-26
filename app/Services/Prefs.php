<?php

declare(strict_types=1);

/** Reader/UI preferences: validation, defaults, storage resolution. */

final class Prefs
{
    public const DEFAULTS = [
        'theme'       => 'auto',   // auto | light | dark
        'reader_font' => 18,       // px 14–24
        'reader_width' => 42,      // rem 34–60
        'reader_leading' => 1.7,   // line-height 1.4–2.2
        'reader_theme' => 'light', // light | sepia | dark
        'motion'      => 'on',     // on | reduced
    ];

    /** Whitelist + clamp a submitted subset of prefs. */
    public static function fromRequest(): array
    {
        $post = $_POST;
        $out = [];

        if (isset($post['theme']) && in_array($post['theme'], ['auto', 'light', 'dark'], true)) {
            $out['theme'] = $post['theme'];
        }
        if (isset($post['motion']) && in_array($post['motion'], ['on', 'reduced'], true)) {
            $out['motion'] = $post['motion'];
        }
        foreach ([
            'reader_font'    => [14, 24],
            'reader_width'   => [34, 60],
            'reader_leading' => null,
        ] as $key => [$min, $max]) {
            if (!isset($post[$key])) continue;
            $v = filter_var($post[$key], FILTER_VALIDATE_FLOAT);
            if ($v === false) continue;
            if ($key === 'reader_leading') {
                $v = round($v * 10) / 10;
                if ($v >= 1.4 && $v <= 2.2) $out[$key] = $v;
                continue;
            }
            $out[$key] = (int)round(max($min, min($max, $v)));
        }
        if (isset($post['reader_theme']) && in_array($post['reader_theme'], ['light', 'sepia', 'dark'], true)) {
            $out['reader_theme'] = $post['reader_theme'];
        }
        return $out;
    }

    /** Effective prefs for the current visitor (user row > session anon > defaults). */
    /** Sanitize an untrusted prefs-shaped array (from sync payload). */
    public static function sanitizeArray(array $raw): array
    {
        $out = [];
        $check = static function (string $key, string $value) {
            return match ($key) {
                'theme'        => in_array($value, ['auto', 'light', 'dark'], true) ? $value : null,
                'motion'       => in_array($value, ['on', 'reduced'], true) ? $value : null,
                'reader_theme' => in_array($value, ['light', 'sepia', 'dark'], true) ? $value : null,
                'reader_font'  => ($f = filter_var($value, FILTER_VALIDATE_FLOAT)) !== false && $f >= 14 && $f <= 24 ? (int)round($f) : null,
                'reader_width' => ($w = filter_var($value, FILTER_VALIDATE_FLOAT)) !== false && $w >= 34 && $w <= 60 ? (int)round($w) : null,
                'reader_leading' => ($l = filter_var($value, FILTER_VALIDATE_FLOAT)) !== false && $l >= 1.4 && $l <= 2.2 ? round($l, 1) : null,
                default => null,
            };
        };
        foreach ($raw as $key => $value) {
            if (!is_scalar($value)) continue;
            $valid = $check((string)$key, (string)$value);
            if ($valid !== null) $out[(string)$key] = $valid;
        }
        return $out;
    }

    public static function current(?array $user): array
    {
        Security::bootSession();
        $prefs = self::DEFAULTS;
        $anon = is_array($_SESSION['anon_prefs'] ?? null) ? $_SESSION['anon_prefs'] : [];
        $stored = is_array($user['prefs'] ?? null) ? $user['prefs'] : [];
        return array_merge($prefs, $anon, $stored);
    }

    public static function clientBootstrap(array $prefs): string
    {
        return json_encode($prefs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
