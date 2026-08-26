<?php

declare(strict_types=1);

/**
 * CatalogRepository — cermin lokal (Supabase `books`) dari katalog AniList.
 * Baris dibuat saat pertama kali judul dilihat / disimpan pengguna,
 * supaya perpustakaan, bookmark dan riwayat punya foreign key yang valid.
 */

final class CatalogRepository
{
    private SupabaseClient $db;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->db = $client ?? new SupabaseClient();
    }

    /** Pastikan baris lokal ada; kembalikan id numerik atau null bila gagal. */
    public function ensureLocal(array $item): ?int
    {
        if (!SupabaseClient::configured()) return null;
        try {
            $existing = $this->findIdByExternal($item['external_id']);
            if ($existing !== null) return $existing;

            $row = [
                'external_id'      => $item['external_id'],
                'source'           => 'anilist',
                'media_type'       => $item['media_type'],
                'title'            => mb_substr((string)$item['title'], 0, 300),
                'alt_title'        => $item['alt_title'] ?? null,
                'author'           => mb_substr((string)($item['author'] ?? 'Tidak tersedia'), 0, 160),
                'artist'           => $item['artist'] ?? null,
                'description'      => $item['description'] ?? null,
                'cover_url'        => $item['cover_url'] ?? null,
                'banner_url'       => $item['banner_url'] ?? null,
                'source_url'       => $item['source_url'],
                'read_url'         => null,
                'language'         => $item['media_type'] === 'manhwa' ? 'ko' : ($item['media_type'] === 'anime' ? 'ja' : 'ja'),
                'publication_year' => $item['year'] ?? null,
                'chapters'         => $item['chapters'] ?? null,
                'volumes'          => $item['volumes'] ?? null,
                'episodes'         => $item['episodes'] ?? null,
                'avg_score'        => $item['score'] ?? null,
                'status_label'     => $item['status_label'] ?? null,
                'downloads'        => (int)($item['popularity'] ?? 0),
                'subjects'         => implode(', ', array_slice((array)$item['genres'], 0, 8)),
            ];

            $res = $this->db->insert('books', $row, upsert: true, onConflict: 'external_id');
            if (!empty($res['data'][0]['id'])) return (int)$res['data'][0]['id'];

            // Konflik race: coba ambil lagi.
            return $this->findIdByExternal($item['external_id']);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findIdByExternal(string $externalId): ?int
    {
        try {
            $row = $this->db->selectOne('books', ['select' => 'id', 'external_id' => 'eq.' . $externalId], privileged: true);
            return $row ? (int)$row['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
