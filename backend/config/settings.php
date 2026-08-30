<?php

declare(strict_types=1);

/**
 * Pure env -> settings array. Nothing here touches Slim, PDO or the
 * container — that composition happens in config/container.php.
 */
return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOL),
    ],

    'cors' => [
        'allowed_origins' => array_filter(array_map(
            'trim',
            explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')
        )),
    ],

    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'yotee',
        'username' => $_ENV['DB_USERNAME'] ?? 'yotee',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'access_token_ttl_seconds' => (int) ($_ENV['JWT_ACCESS_TOKEN_TTL_SECONDS'] ?? 900),
        'refresh_token_ttl_seconds' => (int) ($_ENV['JWT_REFRESH_TOKEN_TTL_SECONDS'] ?? 2592000),
    ],

    'oauth' => [
        'google_client_id' => $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? '',
        'apple_client_id' => $_ENV['APPLE_OAUTH_CLIENT_ID'] ?? '',
    ],

    'rate_limit' => [
        'max_requests' => (int) ($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 60),
        'window_seconds' => (int) ($_ENV['RATE_LIMIT_WINDOW_SECONDS'] ?? 60),
    ],
];
