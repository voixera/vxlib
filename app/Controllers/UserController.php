<?php

declare(strict_types=1);

/** Personal shelves, profile and settings. */

final class UserController
{
    public static function library(): void
    {
        $user = Auth::requireUser();
        $repo = new LibraryRepository();

        $entries = $repo->libraryBooks((int)$user['id']);
        $shelves = ['reading' => [], 'want_to_read' => [], 'completed' => []];
        foreach ($entries as $entry) {
            $shelves[$entry['status']][] = $entry;
        }
        $continueReading = $repo->recentProgress((int)$user['id'], 8);
        $bookmarks = $repo->bookmarks((int)$user['id']);

        page('pages/library', [
            'title'       => 'My library — VoiXLib',
            'description' => 'Your saved books, reading progress and bookmarks.',
            'activeNav'   => 'library',
            'scripts'     => ['library.js'],
        ], [
            'user'        => $user,
            'shelves'     => $shelves,
            'continueReading' => $continueReading,
            'bookmarks'   => $bookmarks,
        ]);
    }

    public static function history(): void
    {
        $user = Auth::requireUser();
        $history = (new LibraryRepository())->history((int)$user['id'], 24);

        page('pages/history', [
            'title'       => 'Riwayat Baca — VoiXLib',
            'description' => 'Judul yang terakhir kamu buka di VoiXLib.',
            'activeNav'   => 'history',
        ], [
            'user'        => $user,
            'history'     => $history,
        ]);
    }

    public static function profile(): void
    {
        $user = Auth::requireUser();
        $repo = new LibraryRepository();
        $userId = (int)$user['id'];

        $saved     = (new SupabaseClient())->select('user_library', ['select' => 'id', 'user_id' => 'eq.' . $userId], withCount: true, privileged: true)['total'] ?? 0;
        $completed = $repo->countCompleted($userId);
        $reading   = $repo->countReading($userId);
        $history   = $repo->history($userId, 12);
        $bookmarkCount = count($repo->bookmarks($userId));

        page('pages/profile', [
            'title'       => 'Profile — VoiXLib',
            'description' => 'Your VoiXLib reading life.',
            'activeNav'   => 'profile',
        ], [
            'user'        => $user,
            'stats'       => [
                'saved'     => $saved,
                'completed' => $completed,
                'reading'   => $reading,
                'bookmarks' => $bookmarkCount,
            ],
            'history'     => $history,
        ]);
    }

    public static function settings(): void
    {
        $user = Auth::user();

        if (is_post()) {
            Security::verifyCsrf();
            if (!RateLimiter::allow('settings_save', 30, 300)) {
                redirect('/settings.php?saved=0');
            }
            $prefs = Prefs::fromRequest();
            if ($user) {
                UserRepository::savePrefsStatic((int)$user['id'], array_merge(is_array($user['prefs'] ?? null) ? $user['prefs'] : [], $prefs));
            } else {
                $_SESSION['anon_prefs'] = array_merge(is_array($_SESSION['anon_prefs'] ?? null) ? $_SESSION['anon_prefs'] : [], $prefs);
            }
            redirect('/settings.php?saved=1');
        }

        $prefs = Prefs::current($user);
        page('pages/settings', [
            'title'       => 'Settings — VoiXLib',
            'description' => 'Theme, reader typography and motion preferences.',
            'activeNav'   => '',
            'scripts'     => ['settings.js'],
        ], [
            'user'        => $user,
            'prefs'       => $prefs,
            'saved'       => ($_GET['saved'] ?? '') === '1',
        ]);
    }
}
