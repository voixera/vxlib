<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** Step 1 of Discord OAuth2: redirect the visitor to Discord with a signed state. */

Security::bootSession();

if (!RateLimiter::allow('oauth_start', 20, 300)) {
    page('errors/500', ['title' => 'Slow down — VoiXLib', 'message' => 'Too many sign-in attempts. Try again shortly.']);
}

if (Auth::check()) redirect('/');

$clientId = (string)Config::get('DISCORD_CLIENT_ID', '');
$redirect = (string)Config::get('DISCORD_REDIRECT_URI', '');
if ($clientId === '' || $redirect === '') {
    page('errors/500', [
        'title'   => 'Sign-in unavailable — VoiXLib',
        'message' => 'Discord sign-in is not configured on this deployment yet. Set DISCORD_CLIENT_ID and DISCORD_REDIRECT_URI in your environment.',
    ]);
}

// Remember where the user wanted to go.
$intended = $_GET['next'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
if (!is_string($intended) || !str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
    $intended = '/';
}
$_SESSION['oauth_intended'] = mb_substr($intended, 0, 200);

$state = bin2hex(random_bytes(24));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_started_at'] = time();

$params = http_build_query([
    'client_id'            => $clientId,
    'redirect_uri'         => $redirect,
    'response_type'        => 'code',
    'scope'                => 'identify email',
    'state'                => $state,
    'prompt'               => 'consent',
]);

redirect('https://discord.com/oauth2/authorize?' . $params);
