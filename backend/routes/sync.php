<?php

declare(strict_types=1);

use App\Controller\SyncController;
use App\Middleware\AuthMiddleware;
use Slim\App;

/** @var App $app */

$app->get('/sync', SyncController::class . ':sync')->add(AuthMiddleware::class);
