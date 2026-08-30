<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A refresh token is a random opaque value; only its SHA-256 hash is ever
 * persisted, so a database leak alone can't be replayed as a live session
 * (PRD §66 general security posture, applied to the one long-lived secret
 * the backend itself issues).
 */
final class RefreshToken
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $tokenHash,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $revokedAt = null,
    ) {
    }

    public function isValid(\DateTimeImmutable $now): bool
    {
        return $this->revokedAt === null && $this->expiresAt > $now;
    }
}
