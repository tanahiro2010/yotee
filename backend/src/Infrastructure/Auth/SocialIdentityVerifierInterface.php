<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Exception\UnauthorizedException;

interface SocialIdentityVerifierInterface
{
    /** @throws UnauthorizedException if $idToken doesn't verify against this provider. */
    public function verify(string $idToken): VerifiedSocialIdentity;
}
