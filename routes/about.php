<?php
/** About — editorial static page (clearly defined static configuration). */
require_once dirname(__DIR__) . '/app/bootstrap.php';
page('pages/about', [
    'title'       => 'About VoiXLib',
    'description' => 'What VoiXLib is, where the books come from, and how it works.',
    'activeNav'   => '',
]);
