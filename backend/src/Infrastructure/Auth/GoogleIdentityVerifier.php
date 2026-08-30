<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Exception\UnauthorizedException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/**
 * Verifies a Google Sign-In ID token entirely offline against Google's
 * published JWKS (no call to Google's tokeninfo endpoint needed). Used only
 * as an OAuth identity proof — no Calendar or other scope is ever requested
 * (see the "Google Login" scope decision recorded in project memory).
 */
final class GoogleIdentityVerifier implements SocialIdentityVerifierInterface
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

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
            throw new UnauthorizedException('Invalid Google ID token');
        }

        if (!in_array($claims->iss ?? null, self::ISSUERS, true)) {
            throw new UnauthorizedException('Invalid Google ID token issuer');
        }

        if (($claims->aud ?? null) !== $this->expectedClientId) {
            throw new UnauthorizedException('Google ID token was not issued for this app');
        }

        $subject = $claims->sub ?? null;
        $email = $claims->email ?? null;
        if (!is_string($subject) || $subject === '' || !is_string($email) || $email === '') {
            throw new UnauthorizedException('Google ID token is missing required claims');
        }

        $displayName = is_string($claims->name ?? null) && $claims->name !== ''
            ? $claims->name
            : $email;

        return new VerifiedSocialIdentity($subject, $email, $displayName);
    }
}
