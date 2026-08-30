<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\AuthProvider;
use App\Domain\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    /** Looks up a User via a linked social identity (e.g. a Google subject id). */
    public function findByProviderIdentity(AuthProvider $provider, string $providerUserId): ?User;

    /** Creates a User and links the given provider identity to it, atomically. */
    public function createWithIdentity(User $user, AuthProvider $provider, string $providerUserId): User;
}
