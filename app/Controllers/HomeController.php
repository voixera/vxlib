<?php

declare(strict_types=1);

/** Beranda — shelf nyata dari AniList; section kosong tidak dirender. */

final class HomeController
{
    public static function index(): void
    {
        $library = new LibraryRepository();

        $shelves = AniListService::shelves();
        $recent  = AniListService::browse(['sort' => 'newest', 'per_page' => 12, 'page' => 1]);

        $heroCovers = array_slice($shelves['trending']['items'] ?? [], 0, 14);

        $continueReading = Auth::check() ? $library->recentProgress(Auth::id(), 8) : [];

        // Statistik: hanya yang benar-benar bisa dihitung dari database sendiri.
        $stats = [];
        if (SupabaseClient::configured()) {
            $db = new SupabaseClient();
            $r = $db->select('reading_history', ['select' => 'id', 'limit' => '1'], withCount: true, privileged: true);
            if (($r['total'] ?? null) !== null) {
                $stats[] = ['value' => (int)$r['total'], 'label' => 'sesi membaca'];
            }
            $u = $db->select('users', ['select' => 'id', 'limit' => '1'], withCount: true, privileged: true);
            if (($u['total'] ?? null) !== null) {
                $stats[] = ['value' => (int)$u['total'], 'label' => 'pembaca'];
            }
            if ($stats === []) $stats = null;
        }

        page('pages/home', [
            'title'       => 'VoiXLib — Temukan cerita yang ingin kamu baca',
            'description' => 'Platform discovery manga dan manhwa dengan metadata nyata dari AniList. Baca langsung di VoiXLib, simpan ke perpustakaan, bookmark, dan lanjutkan kapan pun.',
            'activeNav'   => 'home',
        ], [
            'shelves'         => array_filter($shelves),
            'recent'          => $recent,
            'heroCovers'      => $heroCovers,
            'continueReading' => $continueReading,
            'stats'           => $stats,
        ]);
    }
}
