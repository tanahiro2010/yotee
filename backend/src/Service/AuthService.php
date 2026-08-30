<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\AuthProvider;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\RefreshToken;
use App\Domain\User;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Auth\SocialIdentityVerifierInterface;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\RefreshTokenRepositoryInterface;
use App\Repository\Contract\UserRepositoryInterface;

final class AuthService
{
    private const REFRESH_TOKEN_BYTES = 32;

    /** @param array<string, SocialIdentityVerifierInterface> $identityVerifiers keyed by AuthProvider::value */
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly JwtService $jwt,
        private readonly UuidGenerator $uuids,
        private readonly array $identityVerifiers,
        private readonly int $refreshTokenTtlSeconds,
    ) {
    }

    /** @return array{accessToken: string, refreshToken: string, expiresIn: int, user: User} */
    public function login(AuthProvider $provider, string $idToken): array
    {
        $verifier = $this->identityVerifiers[$provider->value] ?? null;
        if ($verifier === null) {
            throw new UnauthorizedException("No identity verifier configured for provider {$provider->value}");
        }

        $identity = $verifier->verify($idToken);

        $user = $this->users->findByProviderIdentity($provider, $identity->providerUserId);
        if ($user === null) {
            $now = new \DateTimeImmutable();
            $user = $this->users->createWithIdentity(
                new User($this->uuids->generate(), $identity->displayName, $identity->email, $now, $now),
                $provider,
                $identity->providerUserId,
            );
        }

        return [...$this->issueTokenPair($user->id), 'user' => $user];
    }

    /** @return array{accessToken: string, refreshToken: string, expiresIn: int} */
    public function refresh(string $plaintextRefreshToken): array
    {
        $now = new \DateTimeImmutable();
        $record = $this->refreshTokens->findValidByHash(self::hash($plaintextRefreshToken), $now);
        if ($record === null) {
            throw new UnauthorizedException('Refresh token is invalid or expired');
        }

        // Rotate on every use: the old token is dead the moment it's
        // redeemed, so a stolen-and-replayed refresh token can only ever be
        // used once before the legitimate client's next refresh fails loudly.
        $this->refreshTokens->revoke($record->id);

        return $this->issueTokenPair($record->userId);
    }

    public function logout(string $currentUserId, string $plaintextRefreshToken): void
    {
        $now = new \DateTimeImmutable();
        $record = $this->refreshTokens->findValidByHash(self::hash($plaintextRefreshToken), $now);
        if ($record === null) {
            // Already revoked/expired/unknown — logout is idempotent.
            return;
        }

        if (!hash_equals($record->userId, $currentUserId)) {
            throw new ForbiddenException('Refresh token does not belong to the authenticated user');
        }

        $this->refreshTokens->revoke($record->id);
    }

    /** @return array{accessToken: string, refreshToken: string, expiresIn: int} */
    private function issueTokenPair(string $userId): array
    {
        $now = new \DateTimeImmutable();
        $plaintext = bin2hex(random_bytes(self::REFRESH_TOKEN_BYTES));

        $this->refreshTokens->create(new RefreshToken(
            id: $this->uuids->generate(),
            userId: $userId,
            tokenHash: self::hash($plaintext),
            expiresAt: $now->modify("+{$this->refreshTokenTtlSeconds} seconds"),
            createdAt: $now,
        ));

        return [
            'accessToken' => $this->jwt->issueAccessToken($userId),
            'refreshToken' => $plaintext,
            'expiresIn' => $this->jwt->accessTokenTtlSeconds(),
        ];
    }

    private static function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }
}
