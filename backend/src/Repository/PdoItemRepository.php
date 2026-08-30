<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Item;
use App\Domain\ScheduleType;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\ItemRepositoryInterface;

final class PdoItemRepository implements ItemRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(string $id): ?Item
    {
        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function create(Item $item): Item
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO items
                (id, category_id, name, description, schedule_type, schedule_rule, location, url, sort_order, created_at, updated_at, deleted_at)
             VALUES
                (:id, :category_id, :name, :description, :schedule_type, :schedule_rule, :location, :url, :sort_order, :created_at, :updated_at, :deleted_at)'
        );
        $stmt->execute($this->toParams($item));

        return $item;
    }

    public function save(Item $item): Item
    {
        $stmt = $this->pdo->prepare(
            'UPDATE items SET
                name = :name,
                description = :description,
                schedule_type = :schedule_type,
                schedule_rule = :schedule_rule,
                location = :location,
                url = :url,
                sort_order = :sort_order,
                updated_at = :updated_at,
                deleted_at = :deleted_at
             WHERE id = :id'
        );
        $stmt->execute($this->toParams($item));

        return $item;
    }

    public function findByCategoryId(string $categoryId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM items WHERE category_id = :category_id AND deleted_at IS NULL ORDER BY sort_order ASC, created_at ASC'
        );
        $stmt->execute(['category_id' => $categoryId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function findUpdatedSince(array $categoryIds, ?\DateTimeImmutable $since, int $limit): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $idPlaceholders = [];
        $params = [];
        foreach (array_values($categoryIds) as $i => $id) {
            $key = "id{$i}";
            $idPlaceholders[] = ":{$key}";
            $params[$key] = $id;
        }

        $sql = 'SELECT * FROM items WHERE category_id IN (' . implode(',', $idPlaceholders) . ')';
        if ($since !== null) {
            $sql .= ' AND updated_at > :since';
            $params['since'] = DateTimeCodec::toDb($since);
        }
        $sql .= ' ORDER BY updated_at ASC, id ASC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    private function toParams(Item $item): array
    {
        return [
            'id' => $item->id,
            'category_id' => $item->categoryId,
            'name' => $item->name,
            'description' => $item->description,
            'schedule_type' => $item->scheduleType->value,
            'schedule_rule' => json_encode($item->scheduleRule, JSON_THROW_ON_ERROR),
            'location' => $item->location,
            'url' => $item->url,
            'sort_order' => $item->sortOrder,
            'created_at' => DateTimeCodec::toDb($item->createdAt),
            'updated_at' => DateTimeCodec::toDb($item->updatedAt),
            'deleted_at' => $item->deletedAt !== null ? DateTimeCodec::toDb($item->deletedAt) : null,
        ];
    }

    private function hydrate(array $row): Item
    {
        return new Item(
            id: $row['id'],
            categoryId: $row['category_id'],
            name: $row['name'],
            description: $row['description'],
            scheduleType: ScheduleType::from($row['schedule_type']),
            scheduleRule: json_decode($row['schedule_rule'], true, flags: JSON_THROW_ON_ERROR),
            location: $row['location'],
            url: $row['url'],
            sortOrder: (int) $row['sort_order'],
            createdAt: DateTimeCodec::fromDb($row['created_at']),
            updatedAt: DateTimeCodec::fromDb($row['updated_at']),
            deletedAt: DateTimeCodec::fromDbNullable($row['deleted_at']),
        );
    }
}
