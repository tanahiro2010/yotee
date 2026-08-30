<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Category;

interface CategoryRepositoryInterface
{
    public function findById(string $id): ?Category;

    public function create(Category $category): Category;

    /** Persists every mutable field of $category, including `deletedAt` for soft delete (PRD §21). */
    public function save(Category $category): Category;

    /**
     * Category ids owned by this user, used only to scope sync — deliberately
     * includes soft-deleted ones. Excluding them here would drop a deleted
     * Category out of the id set findUpdatedSince() is scoped to, hiding the
     * §21 deletion tombstone from the very owner who deleted it.
     */
    public function findIdsOwnedBy(string $userId): array;

    /** @param string[] $ids @return Category[] Non-deleted Categories among $ids, in no particular order. */
    public function findByIds(array $ids): array;

    /** @return array{items: Category[], nextCursor: ?string} Public, non-deleted Categories matching name/description (PRD §57). */
    public function searchPublic(string $query, ?string $cursor, int $limit): array;

    /**
     * Categories among $categoryIds updated after $since (or all, if $since
     * is null), ordered by updated_at ascending. Includes soft-deleted rows
     * so a subscriber's next sync can reconcile a List being removed (§21).
     *
     * @param string[] $categoryIds
     * @return Category[]
     */
    public function findUpdatedSince(array $categoryIds, ?\DateTimeImmutable $since, int $limit): array;
}
