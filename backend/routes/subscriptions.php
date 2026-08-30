<?php

declare(strict_types=1);

use App\Controller\SubscriptionController;
use App\Middleware\AuthMiddleware;
use Slim\App;

/** @var App $app */

$app->get('/me/subscriptions', SubscriptionController::class . ':list')->add(AuthMiddleware::class);
