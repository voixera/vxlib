<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (isset($_GET['series'], $_GET['chapter'])) {
    MangaController::read();
} elseif (isset($_GET['series'])) {
    MangaController::detail();
} else {
    MangaController::search();
}
