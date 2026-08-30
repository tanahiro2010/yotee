<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\AuthProvider;
use App\Domain\User;
use App\Infrastructure\Database\DateTimeCodec;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\UserRepositoryInterface;

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByProviderIdentity(AuthProvider $provider, string $providerUserId): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.* FROM users u
             INNER JOIN user_identities i ON i.user_id = u.id
             WHERE i.provider = :provider AND i.provider_user_id = :provider_user_id
             LIMIT 1'
        );
        $stmt->execute(['provider' => $provider->value, 'provider_user_id' => $providerUserId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function createWithIdentity(User $user, AuthProvider $provider, string $providerUserId): User
    {
        $this->pdo->beginTransaction();

        try {
            $insertUser = $this->pdo->prepare(
                'INSERT INTO users (id, display_name, email, created_at, updated_at)
                 VALUES (:id, :display_name, :email, :created_at, :updated_at)'
            );
            $insertUser->execute([
                'id' => $user->id,
                'display_name' => $user->displayName,
                'email' => $user->email,
                'created_at' => DateTimeCodec::toDb($user->createdAt),
                'updated_at' => DateTimeCodec::toDb($user->updatedAt),
            ]);

            $insertIdentity = $this->pdo->prepare(
                'INSERT INTO user_identities (id, user_id, provider, provider_user_id, created_at)
                 VALUES (:id, :user_id, :provider, :provider_user_id, :created_at)'
            );
            $insertIdentity->execute([
                'id' => $this->uuids->generate(),
                'user_id' => $user->id,
                'provider' => $provider->value,
                'provider_user_id' => $providerUserId,
                'created_at' => DateTimeCodec::toDb($user->createdAt),
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }

        return $user;
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: $row['id'],
            displayName: $row['display_name'],
            email: $row['email'],
            createdAt: DateTimeCodec::fromDb($row['created_at']),
            updatedAt: DateTimeCodec::fromDb($row['updated_at']),
        );
    }
}
