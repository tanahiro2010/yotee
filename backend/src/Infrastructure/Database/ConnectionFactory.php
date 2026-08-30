<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

/**
 * The only place in the codebase allowed to construct a PDO instance —
 * Repositories receive it via DI, never build their own (PRD §38, §39).
 */
final class ConnectionFactory
{
    /** @param array{host: string, port: int, database: string, username: string, password: string, charset: string} $config */
    public static function create(array $config): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'],
        );

        return new \PDO($dsn, $config['username'], $config['password'], [
            // Real prepared statements (not client-side emulation) — closes a
            // class of edge cases where emulated prepares can be tricked into
            // unsafe interpolation, and lets MySQL itself validate parameter
            // types (PRD §66 "SQL Parameter Binding").
            \PDO::ATTR_EMULATE_PREPARES => false,

            // Fail loudly on any DB error rather than silently continuing —
            // Repositories rely on exceptions bubbling up to the Service
            // layer / ErrorHandlerMiddleware, never on checking return codes.
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,

            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,

            // One round trip per query instead of two (prepare, then
            // execute) for the common case of a statement used once.
            \PDO::ATTR_STRINGIFY_FETCHES => false,

            // Store everything in UTC; the API only ever exchanges
            // `utcDateTime` / IANA-zoned local times, never server-local time.
            self::mysqlInitCommandOption() => "SET time_zone = '+00:00', sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE'",
        ]);
    }

    /**
     * `PDO::MYSQL_ATTR_INIT_COMMAND` is deprecated as of PHP 8.4 in favor of
     * `Pdo\Mysql::ATTR_INIT_COMMAND` (the new driver-specific subclass), but
     * that class doesn't exist yet on 8.2/8.3, which this codebase also has
     * to run on (composer.json requires ^8.2) — so pick whichever constant
     * the running PHP version actually has instead of hardcoding one and
     * either emitting a deprecation notice or breaking outright.
     */
    private static function mysqlInitCommandOption(): int
    {
        return class_exists(\Pdo\Mysql::class) ? \Pdo\Mysql::ATTR_INIT_COMMAND : \PDO::MYSQL_ATTR_INIT_COMMAND;
    }
}
