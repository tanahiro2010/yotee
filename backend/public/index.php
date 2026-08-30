<?php

declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOL);

// A stray PHP warning/notice/deprecation must never reach the client as raw
// HTML mixed into (or before) the JSON body — that's an information leak
// (PRD §66 "Output Escape"; a filesystem path is exactly the kind of
// internal detail this is meant to keep out of a response). Converting every
// error into an exception routes it through ErrorHandlerMiddleware instead,
// which logs it and returns the same clean `{ "error": ... }` envelope as
// any other failure.
error_reporting(E_ALL);
ini_set('display_errors', '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
// Compiled container avoids re-resolving the definition graph on every
// request — a real cost at this call volume once this handles reminder-free
// but still per-request traffic like /sync (PRD §65 Performance).
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->setBasePath('/api/v1');

// Parses `application/json` request bodies into arrays for
// `$request->getParsedBody()` — Controllers never touch `php://input` directly.
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/categories.php';
require __DIR__ . '/../routes/items.php';
require __DIR__ . '/../routes/subscriptions.php';
require __DIR__ . '/../routes/devices.php';
require __DIR__ . '/../routes/sync.php';

$app->add(RateLimitMiddleware::class);
$app->add(CorsMiddleware::class);
$app->add(SecurityHeadersMiddleware::class);

// Added last so it's outermost: it must see every exception thrown by
// routing, every other middleware, and every Controller (PRD §52).
$app->add(ErrorHandlerMiddleware::class);

$app->run();
