<?php

declare(strict_types=1);

/**
 * MangaProviderFactory — kembalikan nama class provider aktif.
 *
 * Provider diimplementasikan dengan method static (konsisten dengan
 * service lain di VoiXLib), sehingga factory mengembalikan class-string.
 * UI/controller memanggil method static lewat nama class ini, sehingga
 * provider bisa diganti tanpa menyentuh tampilan.
 *
 * Ganti via ENV MANGA_PROVIDER (default: weebcentral).
 *
 * @return class-string<MangaProvider&ChapterProvider&ReaderProvider>
 */
final class MangaProviderFactory
{
    public static function className(): string
    {
        $name = strtolower((string)Config::get('MANGA_PROVIDER', 'weebcentral'));
        return match ($name) {
            'weebcentral' => WeebCentralProvider::class,
            default       => WeebCentralProvider::class,
        };
    }
}
