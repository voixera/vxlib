<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

http_response_code(404);
page('errors/404', ['title' => 'This shelf doesn’t exist — VoiXLib', 'activeNav' => ''], ['message' => null]);
