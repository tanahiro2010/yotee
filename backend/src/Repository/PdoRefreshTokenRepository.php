<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\RefreshToken;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\RefreshTokenRepositoryInterface;

final class PdoRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function create(RefreshToken $token): RefreshToken
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO refresh_tokens (id, user_id, token_hash, expires_at, revoked_at, created_at)
             VALUES (:id, :user_id, :token_hash, :expires_at, :revoked_at, :created_at)'
        );
        $stmt->execute([
            'id' => $token->id,
            'user_id' => $token->userId,
            'token_hash' => $token->tokenHash,
            'expires_at' => DateTimeCodec::toDb($token->expiresAt),
            'revoked_at' => $token->revokedAt !== null ? DateTimeCodec::toDb($token->revokedAt) : null,
            'created_at' => DateTimeCodec::toDb($token->createdAt),
        ]);

        return $token;
    }

    public function findValidByHash(string $tokenHash, \DateTimeImmutable $now): ?RefreshToken
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM refresh_tokens
             WHERE token_hash = :token_hash AND revoked_at IS NULL AND expires_at > :now
             LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => $tokenHash,
            'now' => DateTimeCodec::toDb($now),
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new RefreshToken(
            id: $row['id'],
            userId: $row['user_id'],
            tokenHash: $row['token_hash'],
            expiresAt: DateTimeCodec::fromDb($row['expires_at']),
            createdAt: DateTimeCodec::fromDb($row['created_at']),
            revokedAt: DateTimeCodec::fromDbNullable($row['revoked_at']),
        );
    }

    public function revoke(string $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE refresh_tokens SET revoked_at = :revoked_at WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'revoked_at' => DateTimeCodec::toDb(new \DateTimeImmutable()),
        ]);
    }
}
