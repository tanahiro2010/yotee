<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

final class VerifiedSocialIdentity
{
    public function __construct(
        public readonly string $providerUserId,
        public readonly string $email,
        public readonly string $displayName,
    ) {
    }
}
