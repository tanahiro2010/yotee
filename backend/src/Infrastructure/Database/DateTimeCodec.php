<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

/**
 * Every `DATETIME(6)` column is written and read as naive UTC (the
 * connection is pinned to `time_zone = '+00:00'` in ConnectionFactory) —
 * this is the one place that turns that convention into `DateTimeImmutable`
 * and back, so it can't drift between Repositories.
 */
final class DateTimeCodec
{
    private const FORMAT = 'Y-m-d H:i:s.u';

    public static function toDb(\DateTimeImmutable $value): string
    {
        return $value->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT);
    }

    public static function fromDb(string $value): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat(self::FORMAT, $value, new \DateTimeZone('UTC'))
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));

        if ($parsed === false) {
            throw new \RuntimeException("Unparseable datetime from database: {$value}");
        }

        return $parsed;
    }

    public static function fromDbNullable(?string $value): ?\DateTimeImmutable
    {
        return $value === null ? null : self::fromDb($value);
    }
}
