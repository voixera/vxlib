<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

Security::bootSession();

if (Auth::check()) redirect('/');

$required = ($_GET['required'] ?? '') === '1';
$next = $_GET['next'] ?? '';
if (!is_string($next) || !str_starts_with($next, '/') || str_starts_with($next, '//')) $next = '/';

page('pages/login', [
    'title'       => 'Sign in with Discord — VoiXLib',
    'description' => 'Continue to VoiXLib using your Discord account. We only read your identity — never your password.',
    'activeNav'   => '',
    'required'    => $required,
    'next'        => $next,
], ['required' => $required, 'next' => $next]);
