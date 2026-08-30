<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Item;

interface ItemRepositoryInterface
{
    public function findById(string $id): ?Item;

    public function create(Item $item): Item;

    /** Persists every mutable field of $item, including `deletedAt` for soft delete (PRD §21). */
    public function save(Item $item): Item;

    /** @return Item[] Non-deleted Items belonging to $categoryId, in `sortOrder`. */
    public function findByCategoryId(string $categoryId): array;

    /**
     * Items belonging to any of $categoryIds updated after $since (or all,
     * if null), ordered by updated_at ascending. Includes soft-deleted rows.
     *
     * @param string[] $categoryIds
     * @return Item[]
     */
    public function findUpdatedSince(array $categoryIds, ?\DateTimeImmutable $since, int $limit): array;
}
