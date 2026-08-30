<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Exception\UnauthorizedException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

/**
 * Issues and verifies short-lived access tokens only — refresh tokens are a
 * separate, opaque, DB-backed mechanism (see RefreshTokenRepository) so a
 * leaked/stolen refresh token can actually be revoked, unlike a JWT.
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $accessTokenTtlSeconds,
    ) {
        if ($this->secret === '') {
            throw new \InvalidArgumentException('JWT_SECRET must be set');
        }
    }

    public function issueAccessToken(string $userId): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->accessTokenTtlSeconds,
        ], $this->secret, self::ALGORITHM);
    }

    public function accessTokenTtlSeconds(): int
    {
        return $this->accessTokenTtlSeconds;
    }

    /** @throws UnauthorizedException if the token is missing, malformed, expired, or forged. */
    public function verifyAccessToken(string $token): string
    {
        try {
            // Passing a single Key with an explicit algorithm — never derive
            // the algorithm from the token itself — is what closes the
            // classic "alg confusion" attack against JWT libraries.
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException) {
            throw new UnauthorizedException('Access token has expired');
        } catch (SignatureInvalidException) {
            throw new UnauthorizedException('Access token signature is invalid');
        } catch (\Throwable) {
            throw new UnauthorizedException('Access token is invalid');
        }

        $sub = $decoded->sub ?? null;
        if (!is_string($sub) || $sub === '') {
            throw new UnauthorizedException('Access token is invalid');
        }

        return $sub;
    }
}
