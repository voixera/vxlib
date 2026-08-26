<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_gutenberg') AdminController::addGutenberg();
    if ($action === 'edit') AdminController::edit();
    if ($action === 'delete') AdminController::delete();
}
AdminController::dashboard();
