<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Subscription;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\SubscriptionRepositoryInterface;

final class PdoSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function find(string $userId, string $categoryId): ?Subscription
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscriptions WHERE user_id = :user_id AND category_id = :category_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'category_id' => $categoryId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function create(Subscription $subscription): Subscription
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (id, user_id, category_id, created_at) VALUES (:id, :user_id, :category_id, :created_at)'
        );
        $stmt->execute([
            'id' => $subscription->id,
            'user_id' => $subscription->userId,
            'category_id' => $subscription->categoryId,
            'created_at' => DateTimeCodec::toDb($subscription->createdAt),
        ]);

        return $subscription;
    }

    public function delete(string $userId, string $categoryId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM subscriptions WHERE user_id = :user_id AND category_id = :category_id');
        $stmt->execute(['user_id' => $userId, 'category_id' => $categoryId]);
    }

    public function findCategoryIdsSubscribedBy(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT category_id FROM subscriptions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findUserIdsSubscribedTo(string $categoryId): array
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM subscriptions WHERE category_id = :category_id');
        $stmt->execute(['category_id' => $categoryId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findAllForUser(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscriptions WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function findCreatedSince(string $userId, ?\DateTimeImmutable $since, int $limit): array
    {
        $sql = 'SELECT * FROM subscriptions WHERE user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($since !== null) {
            $sql .= ' AND created_at > :since';
            $params['since'] = DateTimeCodec::toDb($since);
        }
        $sql .= ' ORDER BY created_at ASC, id ASC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    private function hydrate(array $row): Subscription
    {
        return new Subscription(
            id: $row['id'],
            userId: $row['user_id'],
            categoryId: $row['category_id'],
            createdAt: DateTimeCodec::fromDb($row['created_at']),
        );
    }
}
