<?php

declare(strict_types=1);

use App\Controller\ItemController;
use App\Middleware\AuthMiddleware;
use Slim\App;

/** @var App $app */

$app->patch('/items/{itemId}', ItemController::class . ':update')->add(AuthMiddleware::class);
$app->delete('/items/{itemId}', ItemController::class . ':delete')->add(AuthMiddleware::class);
