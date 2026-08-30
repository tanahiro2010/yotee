<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Exception\UnauthorizedException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/** Verifies a "Sign in with Apple" identity token against Apple's published JWKS. */
final class AppleIdentityVerifier implements SocialIdentityVerifierInterface
{
    private const JWKS_URL = 'https://appleid.apple.com/auth/keys';
    private const ISSUER = 'https://appleid.apple.com';

    public function __construct(
        private readonly JwksCache $jwksCache,
        private readonly string $expectedClientId,
    ) {
    }

    public function verify(string $idToken): VerifiedSocialIdentity
    {
        try {
            $jwks = $this->jwksCache->get(self::JWKS_URL);
            $keys = JWK::parseKeySet($jwks);
            $claims = JWT::decode($idToken, $keys);
        } catch (\Throwable) {
            throw new UnauthorizedException('Invalid Apple ID token');
        }

        if (($claims->iss ?? null) !== self::ISSUER) {
            throw new UnauthorizedException('Invalid Apple ID token issuer');
        }

        if (($claims->aud ?? null) !== $this->expectedClientId) {
            throw new UnauthorizedException('Apple ID token was not issued for this app');
        }

        $subject = $claims->sub ?? null;
        if (!is_string($subject) || $subject === '') {
            throw new UnauthorizedException('Apple ID token is missing required claims');
        }

        // Apple only includes `email` on the *first* authorization and omits
        // `name` from the ID token entirely (it's sent once, out-of-band, in
        // the initial client-side authorization response) — fall back to a
        // placeholder so User creation never fails on a second login.
        $email = is_string($claims->email ?? null) ? $claims->email : "{$subject}@privaterelay.appleid.local";

        return new VerifiedSocialIdentity($subject, $email, $email);
    }
}
