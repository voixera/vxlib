<?php

declare(strict_types=1);

/**
 * VoiXLib configuration loader.
 * Reads .env from project root (outside public web root).
 */

final class Config
{
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;
        self::$loaded = true;

        // Sensible defaults; .env overrides.
        self::$values = [
            'APP_URL'          => 'http://localhost:8000',
            'APP_ENV'          => 'production',
            'DISCORD_CLIENT_ID' => '',
            'DISCORD_CLIENT_SECRET' => '',
            'DISCORD_REDIRECT_URI' => '',
            'SUPABASE_URL'     => '',
            'SUPABASE_ANON_KEY' => '',
            'SUPABASE_SERVICE_ROLE_KEY' => '',
            'ADMIN_DISCORD_IDS' => '',
            'ANIPUB_API_URL'    => 'https://www.anipub.xyz',
            'MANGA_READER_API_URL' => 'http://komikato.bugs.today',
            'MANGADEX_API_URL'   => 'https://api.mangadex.org',
            'FEBRY_MANGA_API_URL' => 'https://mangamint.kaedenoki.net/api',
            'SESSION_NAME'     => 'voixlib_session',
        ];

        $envFile = dirname(__DIR__) . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                $key = trim(substr($line, 0, $pos));
                $val = trim(substr($line, $pos + 1));
                $val = trim($val, "\"'");
                if ($key !== '') self::$values[$key] = $val;
            }
        }

        // Real environment variables take precedence over the file.
        foreach (array_keys(self::$values) as $key) {
            $v = getenv($key);
            if ($v !== false && $v !== '') self::$values[$key] = $v;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();
        $v = self::$values[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function adminDiscordIds(): array
    {
        $raw = self::get('ADMIN_DISCORD_IDS', '');
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
