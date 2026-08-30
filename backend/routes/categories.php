<?php

declare(strict_types=1);

use App\Controller\CategoryController;
use App\Controller\ItemController;
use App\Controller\ReportController;
use App\Controller\SubscriptionController;
use App\Middleware\AuthMiddleware;
use App\Middleware\OptionalAuthMiddleware;
use Slim\App;

/** @var App $app */

// /categories/search is registered as a literal path, so it never collides
// with the /categories/{id} pattern below regardless of declaration order
// (Slim's underlying FastRoute dispatcher matches literal segments first).
$app->get('/categories/search', CategoryController::class . ':search');

$app->post('/categories', CategoryController::class . ':create')->add(AuthMiddleware::class);

// Optional auth: an owner needs to be identified to see their own `private`
// List; everyone else reads `unlisted`/`public` anonymously (PRD §17).
$app->get('/categories/{id}', CategoryController::class . ':get')->add(OptionalAuthMiddleware::class);

$app->patch('/categories/{id}', CategoryController::class . ':update')->add(AuthMiddleware::class);
$app->delete('/categories/{id}', CategoryController::class . ':delete')->add(AuthMiddleware::class);

$app->post('/categories/{categoryId}/items', ItemController::class . ':create')->add(AuthMiddleware::class);

$app->post('/categories/{categoryId}/subscribe', SubscriptionController::class . ':subscribe')->add(AuthMiddleware::class);
$app->delete('/categories/{categoryId}/subscribe', SubscriptionController::class . ':unsubscribe')->add(AuthMiddleware::class);

$app->post('/categories/{categoryId}/report', ReportController::class . ':report')->add(AuthMiddleware::class);
