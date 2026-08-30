<?php

declare(strict_types=1);

use App\Infrastructure\Auth\AppleIdentityVerifier;
use App\Infrastructure\Auth\GoogleIdentityVerifier;
use App\Infrastructure\Auth\JwksCache;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Push\LogPushNotificationDispatcher;
use App\Infrastructure\Push\PushNotificationDispatcherInterface;
use App\Infrastructure\RateLimit\ApcuRateLimiter;
use App\Infrastructure\RateLimit\InMemoryRateLimiter;
use App\Infrastructure\RateLimit\RateLimiterInterface;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\OptionalAuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\DeviceRepositoryInterface;
use App\Repository\Contract\ItemRepositoryInterface;
use App\Repository\Contract\RefreshTokenRepositoryInterface;
use App\Repository\Contract\ReportRepositoryInterface;
use App\Repository\Contract\SubscriptionRepositoryInterface;
use App\Repository\Contract\UserRepositoryInterface;
use App\Repository\PdoCategoryRepository;
use App\Repository\PdoDeviceRepository;
use App\Repository\PdoItemRepository;
use App\Repository\PdoRefreshTokenRepository;
use App\Repository\PdoReportRepository;
use App\Repository\PdoSubscriptionRepository;
use App\Repository\PdoUserRepository;
use App\Service\AuthService;
use App\Service\CategoryService;
use App\Service\DeviceService;
use App\Service\ItemService;
use App\Service\ReportService;
use App\Service\SubscriptionService;
use App\Service\SyncService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Pure wiring — every binding here just constructs an object from other
 * bindings/settings. No binding may contain business logic; that always
 * belongs in a Service (PRD §35 layering).
 */
return [
    'settings' => require __DIR__ . '/settings.php',

    LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
        $debug = $c->get('settings')['app']['debug'];
        $logger = new Logger('yotee');
        $logger->pushHandler(new StreamHandler('php://stderr', $debug ? Logger::DEBUG : Logger::INFO));

        return $logger;
    },

    ResponseFactoryInterface::class => static fn (): ResponseFactoryInterface => new ResponseFactory(),

    PDO::class => static fn (ContainerInterface $c): PDO => ConnectionFactory::create($c->get('settings')['db']),

    UuidGenerator::class => static fn (): UuidGenerator => new UuidGenerator(),

    JwtService::class => function (ContainerInterface $c): JwtService {
        $jwt = $c->get('settings')['jwt'];

        return new JwtService($jwt['secret'], $jwt['access_token_ttl_seconds']);
    },

    JwksCache::class => static fn (): JwksCache => new JwksCache(sys_get_temp_dir() . '/yotee-jwks-cache'),

    GoogleIdentityVerifier::class => fn (ContainerInterface $c): GoogleIdentityVerifier => new GoogleIdentityVerifier(
        $c->get(JwksCache::class),
        $c->get('settings')['oauth']['google_client_id'],
    ),

    AppleIdentityVerifier::class => fn (ContainerInterface $c): AppleIdentityVerifier => new AppleIdentityVerifier(
        $c->get(JwksCache::class),
        $c->get('settings')['oauth']['apple_client_id'],
    ),

    // Falls back to a per-process limiter outside PHP-FPM (e.g. `php -S`
    // local dev) where APCu may not be loaded — see InMemoryRateLimiter.
    RateLimiterInterface::class => static fn (): RateLimiterInterface => extension_loaded('apcu')
        ? new ApcuRateLimiter()
        : new InMemoryRateLimiter(),

    PushNotificationDispatcherInterface::class => fn (ContainerInterface $c): PushNotificationDispatcherInterface =>
        new LogPushNotificationDispatcher($c->get(LoggerInterface::class)),

    // --- Repositories ---
    UserRepositoryInterface::class => fn (ContainerInterface $c): UserRepositoryInterface =>
        new PdoUserRepository($c->get(PDO::class), $c->get(UuidGenerator::class)),
    RefreshTokenRepositoryInterface::class => fn (ContainerInterface $c): RefreshTokenRepositoryInterface =>
        new PdoRefreshTokenRepository($c->get(PDO::class)),
    CategoryRepositoryInterface::class => fn (ContainerInterface $c): CategoryRepositoryInterface =>
        new PdoCategoryRepository($c->get(PDO::class)),
    ItemRepositoryInterface::class => fn (ContainerInterface $c): ItemRepositoryInterface =>
        new PdoItemRepository($c->get(PDO::class)),
    SubscriptionRepositoryInterface::class => fn (ContainerInterface $c): SubscriptionRepositoryInterface =>
        new PdoSubscriptionRepository($c->get(PDO::class)),
    DeviceRepositoryInterface::class => fn (ContainerInterface $c): DeviceRepositoryInterface =>
        new PdoDeviceRepository($c->get(PDO::class)),
    ReportRepositoryInterface::class => fn (ContainerInterface $c): ReportRepositoryInterface =>
        new PdoReportRepository($c->get(PDO::class)),

    // --- Services ---
    AuthService::class => function (ContainerInterface $c): AuthService {
        return new AuthService(
            $c->get(UserRepositoryInterface::class),
            $c->get(RefreshTokenRepositoryInterface::class),
            $c->get(JwtService::class),
            $c->get(UuidGenerator::class),
            [
                'google' => $c->get(GoogleIdentityVerifier::class),
                'apple' => $c->get(AppleIdentityVerifier::class),
            ],
            $c->get('settings')['jwt']['refresh_token_ttl_seconds'],
        );
    },
    CategoryService::class => fn (ContainerInterface $c): CategoryService => new CategoryService(
        $c->get(CategoryRepositoryInterface::class),
        $c->get(ItemRepositoryInterface::class),
        $c->get(UuidGenerator::class),
    ),
    ItemService::class => fn (ContainerInterface $c): ItemService => new ItemService(
        $c->get(ItemRepositoryInterface::class),
        $c->get(CategoryRepositoryInterface::class),
        $c->get(SubscriptionRepositoryInterface::class),
        $c->get(DeviceRepositoryInterface::class),
        $c->get(PushNotificationDispatcherInterface::class),
        $c->get(UuidGenerator::class),
    ),
    SubscriptionService::class => fn (ContainerInterface $c): SubscriptionService => new SubscriptionService(
        $c->get(SubscriptionRepositoryInterface::class),
        $c->get(CategoryRepositoryInterface::class),
        $c->get(UuidGenerator::class),
    ),
    DeviceService::class => fn (ContainerInterface $c): DeviceService => new DeviceService(
        $c->get(DeviceRepositoryInterface::class),
        $c->get(UuidGenerator::class),
    ),
    ReportService::class => fn (ContainerInterface $c): ReportService => new ReportService(
        $c->get(ReportRepositoryInterface::class),
        $c->get(CategoryRepositoryInterface::class),
        $c->get(UuidGenerator::class),
    ),
    SyncService::class => fn (ContainerInterface $c): SyncService => new SyncService(
        $c->get(CategoryRepositoryInterface::class),
        $c->get(ItemRepositoryInterface::class),
        $c->get(SubscriptionRepositoryInterface::class),
    ),

    // --- Middleware ---
    AuthMiddleware::class => fn (ContainerInterface $c): AuthMiddleware => new AuthMiddleware($c->get(JwtService::class)),
    OptionalAuthMiddleware::class => fn (ContainerInterface $c): OptionalAuthMiddleware =>
        new OptionalAuthMiddleware($c->get(JwtService::class)),
    ErrorHandlerMiddleware::class => fn (ContainerInterface $c): ErrorHandlerMiddleware => new ErrorHandlerMiddleware(
        $c->get(ResponseFactoryInterface::class),
        $c->get(LoggerInterface::class),
        $c->get('settings')['app']['debug'],
    ),
    SecurityHeadersMiddleware::class => static fn (): SecurityHeadersMiddleware => new SecurityHeadersMiddleware(),
    CorsMiddleware::class => fn (ContainerInterface $c): CorsMiddleware => new CorsMiddleware(
        $c->get(ResponseFactoryInterface::class),
        $c->get('settings')['cors']['allowed_origins'],
    ),
    RateLimitMiddleware::class => function (ContainerInterface $c): RateLimitMiddleware {
        $rateLimit = $c->get('settings')['rate_limit'];

        return new RateLimitMiddleware($c->get(RateLimiterInterface::class), $rateLimit['max_requests'], $rateLimit['window_seconds']);
    },
];
