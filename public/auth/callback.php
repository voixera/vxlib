<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/**
 * Step 2 of Discord OAuth2: validate state, exchange the code, load the
 * Discord profile, upsert the VoiXLib user and open a session.
 */

Security::bootSession();

$fail = function (string $reason): never {
    page('errors/oauth', [
        'title'  => 'Sign-in failed — VoiXLib',
        'reason' => $reason,
    ]);
};

if (!RateLimiter::allow('oauth_callback', 20, 300)) $fail('Too many sign-in attempts from your network. Wait five minutes and try again.');

// ── Validate the OAuth response shape ────────────────────────────────
$error = $_GET['error'] ?? null;
if (is_string($error) && $error !== '') {
    $fail($error === 'access_denied'
        ? 'You declined the authorization prompt in Discord.'
        : 'Discord reported: ' . htmlspecialchars($error, ENT_QUOTES));
}

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
if (!is_string($code) || strlen($code) < 8 || strlen($code) > 256) $fail('Discord did not return a valid authorization code.');
if (!is_string($state) || strlen($state) < 16) $fail('The sign-in response was missing its state parameter.');

// ── State must match what we issued (CSRF defense for the flow) ──────
$sentState = $_SESSION['oauth_state'] ?? '';
$startedAt = (int)($_SESSION['oauth_started_at'] ?? 0);
unset($_SESSION['oauth_state'], $_SESSION['oauth_started_at']);

if ($sentState === '' || !hash_equals($sentState, $state)) $fail('State mismatch — please start the sign-in again.');
if ($startedAt === 0 || time() - $startedAt > 600) $fail('That sign-in attempt expired. Please try again.');

// ── Exchange code for tokens ─────────────────────────────────────────
$token = self_exchangeCode((string)$code);
if ($token === null) $fail('We could not exchange the authorization code with Discord. Please try again.');

// ── Load the Discord profile ─────────────────────────────────────────
$profile = Http::getJson('https://discord.com/api/v10/users/@me', 15, [
    'Authorization: Bearer ' . $token['access_token'],
]);
if (!$profile || empty($profile['id'])) $fail('We reached Discord but could not read your profile.');

if (!preg_match('/^\d{5,25}$/', (string)$profile['id'])) $fail('Discord returned an unexpected user identifier.');

$email = isset($profile['email']) && is_string($profile['email'])
    && filter_var($profile['email'], FILTER_VALIDATE_EMAIL)
    && (($profile['verified'] ?? false) === true)
    ? $profile['email']
    : null;

// ── Upsert into Supabase & open the session ──────────────────────────
$userRow = (new UserRepository())->upsertFromDiscord($profile, $email);
if (!$userRow) $fail('Your Discord identity is valid, but the VoiXLib account service is unreachable right now. Try again soon.');

Auth::login($userRow);

$intended = $_SESSION['oauth_intended'] ?? '/';
unset($_SESSION['oauth_intended']);
if (!is_string($intended) || !str_starts_with($intended, '/') || str_starts_with($intended, '//')) $intended = '/';

redirect($intended);

/** Exchange the authorization code for an access token. */
function self_exchangeCode(string $code): ?array
{
    $ch = curl_init('https://discord.com/api/v10/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => Config::get('DISCORD_CLIENT_ID'),
            'client_secret' => Config::get('DISCORD_CLIENT_SECRET'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => Config::get('DISCORD_REDIRECT_URI'),
        ]),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($status !== 200 || !is_string($raw)) return null;
    $data = json_decode($raw, true);
    return is_array($data) && !empty($data['access_token']) ? $data : null;
}
