<?php

declare(strict_types=1);

use App\Controller\DeviceController;
use App\Middleware\AuthMiddleware;
use Slim\App;

/** @var App $app */

$app->post('/devices', DeviceController::class . ':register')->add(AuthMiddleware::class);
$app->delete('/devices/{id}', DeviceController::class . ':unregister')->add(AuthMiddleware::class);
