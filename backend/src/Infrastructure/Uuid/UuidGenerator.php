<?php

declare(strict_types=1);

namespace App\Infrastructure\Uuid;

use Ramsey\Uuid\Uuid;

/**
 * Every public API entity id is UUID-style, never a raw auto-increment DB id
 * (PRD §40) — this is the single place that generates them.
 */
final class UuidGenerator
{
    public function generate(): string
    {
        return Uuid::uuid7()->toString();
    }
}
