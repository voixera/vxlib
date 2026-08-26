<?php

declare(strict_types=1);

/**
 * ReaderController — pembaca flipbook untuk buku domain publik (Project Gutenberg).
 * Teks nyata & legal untuk didistribusikan; metadata dari Gutendex.
 */

final class ReaderController
{
    public static function show(): void
    {
        $id = GutenbergService::sanitizeId((string)($_GET['id'] ?? ''));
        if ($id === null) {
            redirect('/klasik');
        }

        $meta = GutenbergService::meta($id);
        if (!$meta) {
            http_response_code(404);
            page('errors/404', ['title' => 'Tidak ditemukan — VoiXLib', 'activeNav' => 'klasik'], ['message' => 'Buku ini tidak ada di katalog domain publik.']);
            return;
        }

        $authors = (array)($meta['authors'] ?? []);
        $author = $authors[0]['name'] ?? 'Anonim';
        $readUrl = self::htmlFormatUrl($meta['formats'] ?? [], $id);
        $content = $readUrl !== null ? GutenbergService::readableContent($readUrl, $id) : null;

        if (!$content || empty($content['chapters'])) {
            page('errors/404', [
                'title'       => 'Bacaan tidak tersedia — VoiXLib',
                'activeNav'   => 'klasik',
            ], [
                'message'     => 'Salinan baca untuk buku ini sedang tidak bisa dimuat.',
                'backHref'    => '/klasik',
                'backLabel'   => 'Kembali ke Klasik',
                'sourceUrl'   => 'https://www.gutenberg.org/ebooks/' . $id,
            ]);
            return;
        }

        page('pages/reader', [
            'title'      => 'Membaca ' . $meta['title'] . ' — VoiXLib',
            'chromeless' => true,
            'scripts'    => ['reader.js'],
        ], [
            'bookId'   => 'gutenberg:' . $id,
            'gbId'     => $id,
            'title'    => (string)$meta['title'],
            'author'   => (string)$author,
            'chapters' => array_values(array_map(fn($c, $i) => [
                'index' => $i,
                'title' => (string)($c['title'] ?? ('Bagian ' . ($i + 1))),
                'html'  => (string)($c['html'] ?? ''),
            ], $content['chapters'], array_keys($content['chapters']))),
        ]);
    }

    /** @return array<string,mixed> */
    private static function htmlFormatUrl(array $formats, int $id): ?string
    {
        foreach ($formats as $type => $url) {
            if (str_starts_with((string)$type, 'text/html')) return (string)$url;
        }
        return is_array($formats) && isset($formats['application/xhtml+xml']) ? (string)$formats['application/xhtml+xml'] : 'https://www.gutenberg.org/ebooks/' . $id . '.html.images';
    }
}
