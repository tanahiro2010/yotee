<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $token): RefreshToken;

    /** Looks up an unrevoked, unexpired token by the SHA-256 hash of its plaintext value. */
    public function findValidByHash(string $tokenHash, \DateTimeImmutable $now): ?RefreshToken;

    public function revoke(string $id): void;
}
