<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Middleware\AuthMiddleware;
use Slim\App;

/** @var App $app */

$app->post('/auth/login', AuthController::class . ':login');
$app->post('/auth/refresh', AuthController::class . ':refresh');
$app->post('/auth/logout', AuthController::class . ':logout')->add(AuthMiddleware::class);
$app->get('/me', AuthController::class . ':me')->add(AuthMiddleware::class);
