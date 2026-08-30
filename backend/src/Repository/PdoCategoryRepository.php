<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Category;
use App\Domain\CategoryVisibility;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\CategoryRepositoryInterface;

final class PdoCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(string $id): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function create(Category $category): Category
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories
                (id, owner_id, name, description, visibility, timezone, version, recommended_reminder, created_at, updated_at, deleted_at)
             VALUES
                (:id, :owner_id, :name, :description, :visibility, :timezone, :version, :recommended_reminder, :created_at, :updated_at, :deleted_at)'
        );
        $stmt->execute($this->toParams($category));

        return $category;
    }

    public function save(Category $category): Category
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET
                name = :name,
                description = :description,
                visibility = :visibility,
                timezone = :timezone,
                version = :version,
                recommended_reminder = :recommended_reminder,
                updated_at = :updated_at,
                deleted_at = :deleted_at
             WHERE id = :id'
        );
        // Deliberately a narrower param set than create()'s — owner_id and
        // created_at never change after insert. Passing toParams()'s extra
        // keys here would throw under real (non-emulated) prepared
        // statements: MySQL's native protocol rejects a bound value with no
        // matching placeholder in the query ("Invalid parameter number").
        $stmt->execute([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'visibility' => $category->visibility->value,
            'timezone' => $category->timezone,
            'version' => $category->version,
            'recommended_reminder' => $category->recommendedReminder !== null
                ? json_encode($category->recommendedReminder, JSON_THROW_ON_ERROR)
                : null,
            'updated_at' => DateTimeCodec::toDb($category->updatedAt),
            'deleted_at' => $category->deletedAt !== null ? DateTimeCodec::toDb($category->deletedAt) : null,
        ]);

        return $category;
    }

    public function findIdsOwnedBy(string $userId): array
    {
        // Deliberately not filtering deleted_at — see the interface docblock.
        $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE owner_id = :owner_id');
        $stmt->execute(['owner_id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $key = "id{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $id;
        }

        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE id IN (' . implode(',', $placeholders) . ') AND deleted_at IS NULL'
        );
        $stmt->execute($params);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function searchPublic(string $query, ?string $cursor, int $limit): array
    {
        // Keyset-style relevance pagination is a lot of machinery for a
        // "discover" search box; an offset cursor is a deliberate, documented
        // simplification — deep pagination here is not a priority (PRD §57).
        $offset = self::decodeOffsetCursor($cursor);

        // MySQL's ngram FULLTEXT parser needs at least one 2-character
        // n-gram to match anything (PRD §65 note in the categories
        // migration); fall back to a LIKE scan for single-character queries
        // rather than silently returning nothing.
        if (mb_strlen($query) >= 2) {
            // Real (non-emulated, see ConnectionFactory) prepared statements
            // can't bind the same named placeholder to two different `?`
            // occurrences — each repeat needs its own name even though both
            // get the same value.
            $stmt = $this->pdo->prepare(
                "SELECT *, MATCH(name, description) AGAINST(:query_select IN NATURAL LANGUAGE MODE) AS relevance
                 FROM categories
                 WHERE visibility = 'public' AND deleted_at IS NULL
                   AND MATCH(name, description) AGAINST(:query_where IN NATURAL LANGUAGE MODE)
                 ORDER BY relevance DESC, id ASC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue('query_select', $query, \PDO::PARAM_STR);
            $stmt->bindValue('query_where', $query, \PDO::PARAM_STR);
        } else {
            $likePattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
            $stmt = $this->pdo->prepare(
                "SELECT * FROM categories
                 WHERE visibility = 'public' AND deleted_at IS NULL
                   AND (name LIKE :like_name OR description LIKE :like_description)
                 ORDER BY created_at DESC, id ASC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue('like_name', $likePattern, \PDO::PARAM_STR);
            $stmt->bindValue('like_description', $likePattern, \PDO::PARAM_STR);
        }

        // Fetch one extra row so we know whether a next page actually exists
        // without a second COUNT(*) query.
        $stmt->bindValue('limit', $limit + 1, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);

        return [
            'items' => array_map($this->hydrate(...), $rows),
            'nextCursor' => $hasMore ? self::encodeOffsetCursor($offset + $limit) : null,
        ];
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

        $sql = 'SELECT * FROM categories WHERE id IN (' . implode(',', $idPlaceholders) . ')';
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

    private static function encodeOffsetCursor(int $offset): string
    {
        return base64_encode((string) $offset);
    }

    private static function decodeOffsetCursor(?string $cursor): int
    {
        if ($cursor === null) {
            return 0;
        }

        $decoded = base64_decode($cursor, true);

        return $decoded !== false && ctype_digit($decoded) ? (int) $decoded : 0;
    }

    private function toParams(Category $category): array
    {
        return [
            'id' => $category->id,
            'owner_id' => $category->ownerId,
            'name' => $category->name,
            'description' => $category->description,
            'visibility' => $category->visibility->value,
            'timezone' => $category->timezone,
            'version' => $category->version,
            'recommended_reminder' => $category->recommendedReminder !== null
                ? json_encode($category->recommendedReminder, JSON_THROW_ON_ERROR)
                : null,
            'created_at' => DateTimeCodec::toDb($category->createdAt),
            'updated_at' => DateTimeCodec::toDb($category->updatedAt),
            'deleted_at' => $category->deletedAt !== null ? DateTimeCodec::toDb($category->deletedAt) : null,
        ];
    }

    private function hydrate(array $row): Category
    {
        return new Category(
            id: $row['id'],
            ownerId: $row['owner_id'],
            name: $row['name'],
            description: $row['description'],
            visibility: CategoryVisibility::from($row['visibility']),
            timezone: $row['timezone'],
            version: (int) $row['version'],
            recommendedReminder: $row['recommended_reminder'] !== null
                ? json_decode($row['recommended_reminder'], true, flags: JSON_THROW_ON_ERROR)
                : null,
            createdAt: DateTimeCodec::fromDb($row['created_at']),
            updatedAt: DateTimeCodec::fromDb($row['updated_at']),
            deletedAt: DateTimeCodec::fromDbNullable($row['deleted_at']),
        );
    }
}
