<?php

declare(strict_types=1);

/** Homepage assembly. All numbers come from live Supabase queries — nothing faked. */

final class HomeController
{
    public static function index(): void
    {
        $books   = new BookRepository();
        $library = new LibraryRepository();

        $featured  = $books->search(['featured' => true, 'per_page' => 6]);
        if (empty($featured['books'])) {
            $featured = $books->search(['sort' => 'popular', 'per_page' => 6]);
        }
        $trending  = $books->search(['sort' => 'popular', 'per_page' => 12]);
        $recent    = $books->search(['sort' => 'newest', 'per_page' => 12]);
        $animeFeature = $books->search(['category' => 'anime', 'per_page' => 6]);
        $mangaFeature = $books->search(['category' => 'manga', 'per_page' => 6]);

        $continueReading = Auth::check() ? $library->recentProgress(Auth::id(), 8) : [];

        // Real counts; hidden entirely when Supabase is unreachable.
        $stats = null;
        if (SupabaseClient::configured()) {
            $db = new SupabaseClient();
            $bookCount = $books->countAll();
            $userCount = null;
            $readCount = null;
            if ($bookCount !== null) {
                $u = $db->select('users', ['select' => 'id', 'limit' => '1'], withCount: true, privileged: true);
                $r = $db->select('reading_history', ['select' => 'id', 'limit' => '1'], withCount: true, privileged: true);
                $userCount = $u['total'];
                $readCount = $r['total'];
                $stats = [
                    'books' => $bookCount,
                    'readers' => $userCount,
                    'opens' => $readCount,
                ];
            }
        }

        page('pages/home', [
            'title'       => 'VoiXLib — Read beyond the shelf',
            'description' => 'A calm digital library of real, free books. Discover classics, read them in the browser, keep your place.',
            'activeNav'   => 'home',
        ], [
            'featured'    => $featured,
            'trending'    => $trending,
            'recent'      => $recent,
            'continueReading' => $continueReading,
            'stats'       => $stats,
            'categories'  => BookRepository::CATEGORIES,
            'animeFeature' => $animeFeature,
            'mangaFeature' => $mangaFeature,
        ]);
    }
}
