<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Device;
use App\Domain\DevicePlatform;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\DeviceRepositoryInterface;

final class PdoDeviceRepository implements DeviceRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(string $id): ?Device
    {
        $stmt = $this->pdo->prepare('SELECT * FROM devices WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function register(Device $device): Device
    {
        // Re-registering the same (user, pushToken) pair just refreshes
        // lastSeenAt/updatedAt instead of accumulating duplicate rows —
        // one round trip via ON DUPLICATE KEY, no separate exists-check.
        $stmt = $this->pdo->prepare(
            'INSERT INTO devices (id, user_id, platform, push_token, last_seen_at, created_at, updated_at)
             VALUES (:id, :user_id, :platform, :push_token, :last_seen_at, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                platform = VALUES(platform),
                last_seen_at = VALUES(last_seen_at),
                updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'id' => $device->id,
            'user_id' => $device->userId,
            'platform' => $device->platform->value,
            'push_token' => $device->pushToken,
            'last_seen_at' => $device->lastSeenAt !== null ? DateTimeCodec::toDb($device->lastSeenAt) : null,
            'created_at' => DateTimeCodec::toDb($device->createdAt),
            'updated_at' => DateTimeCodec::toDb($device->updatedAt),
        ]);

        return $this->find($device->userId, $device->pushToken) ?? $device;
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM devices WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findPushTokensForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($userIds) as $i => $userId) {
            $key = "user{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $userId;
        }

        $stmt = $this->pdo->prepare(
            'SELECT push_token FROM devices WHERE user_id IN (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function find(string $userId, string $pushToken): ?Device
    {
        $stmt = $this->pdo->prepare('SELECT * FROM devices WHERE user_id = :user_id AND push_token = :push_token LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'push_token' => $pushToken]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    private function hydrate(array $row): Device
    {
        return new Device(
            id: $row['id'],
            userId: $row['user_id'],
            platform: DevicePlatform::from($row['platform']),
            pushToken: $row['push_token'],
            lastSeenAt: DateTimeCodec::fromDbNullable($row['last_seen_at']),
            createdAt: DateTimeCodec::fromDb($row['created_at']),
            updatedAt: DateTimeCodec::fromDb($row['updated_at']),
        );
    }
}
