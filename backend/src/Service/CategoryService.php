<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Category;
use App\Domain\CategoryVisibility;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\NotFoundException;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\ItemRepositoryInterface;

final class CategoryService
{
    private const SEARCH_DEFAULT_LIMIT = 20;
    private const SEARCH_MAX_LIMIT = 50;

    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ItemRepositoryInterface $items,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function create(string $ownerId, array $validated): Category
    {
        $now = new \DateTimeImmutable();

        return $this->categories->create(new Category(
            id: $this->uuids->generate(),
            ownerId: $ownerId,
            name: $validated['name'],
            description: $validated['description'],
            visibility: $validated['visibility'],
            timezone: $validated['timezone'],
            version: 1,
            recommendedReminder: $validated['recommendedReminder'],
            createdAt: $now,
            updatedAt: $now,
        ));
    }

    /** @return array{category: Category, items: array} */
    public function getDetail(string $categoryId, ?string $currentUserId): array
    {
        $category = $this->findReadable($categoryId, $currentUserId);

        return ['category' => $category, 'items' => $this->items->findByCategoryId($category->id)];
    }

    public function update(string $categoryId, string $currentUserId, array $patch): Category
    {
        $category = $this->findOwned($categoryId, $currentUserId);

        // Metadata edits (rename, description, visibility, recommended
        // reminder default) don't require any subscriber to reschedule a
        // Local Notification, so — unlike Item mutations (PRD §20, §37) —
        // this deliberately does NOT bump `version`. It still updates
        // `updated_at`, so the row is picked up by the next delta sync
        // regardless (see SyncService).
        $updated = new Category(
            id: $category->id,
            ownerId: $category->ownerId,
            name: $patch['name'] ?? $category->name,
            description: array_key_exists('description', $patch) ? $patch['description'] : $category->description,
            visibility: $patch['visibility'] ?? $category->visibility,
            timezone: $patch['timezone'] ?? $category->timezone,
            version: $category->version,
            recommendedReminder: array_key_exists('recommendedReminder', $patch)
                ? $patch['recommendedReminder']
                : $category->recommendedReminder,
            createdAt: $category->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );

        return $this->categories->save($updated);
    }

    public function delete(string $categoryId, string $currentUserId): void
    {
        $category = $this->findOwned($categoryId, $currentUserId);

        $this->categories->save(new Category(
            id: $category->id,
            ownerId: $category->ownerId,
            name: $category->name,
            description: $category->description,
            visibility: $category->visibility,
            timezone: $category->timezone,
            version: $category->version,
            recommendedReminder: $category->recommendedReminder,
            createdAt: $category->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: new \DateTimeImmutable(),
        ));
    }

    /** @return array{items: Category[], nextCursor: ?string} */
    public function search(string $query, ?string $cursor, ?int $limit): array
    {
        $limit = min(max($limit ?? self::SEARCH_DEFAULT_LIMIT, 1), self::SEARCH_MAX_LIMIT);

        return $this->categories->searchPublic(trim($query), $cursor, $limit);
    }

    /**
     * Fetches a Category enforcing PRD §17 visibility rules for reads:
     * `private` is visible only to its owner. A non-owner probing a private
     * id gets the same 404 a genuinely-missing id would — existence of a
     * private List is not something an unauthorized caller should be able
     * to confirm.
     */
    public function findReadable(string $categoryId, ?string $currentUserId): Category
    {
        $category = $this->categories->findById($categoryId);

        if ($category === null || $category->isDeleted()) {
            throw new NotFoundException('Category');
        }

        $isPrivate = $category->visibility === CategoryVisibility::Private;
        $isOwner = $currentUserId !== null && $category->isOwnedBy($currentUserId);

        if ($isPrivate && !$isOwner) {
            throw new NotFoundException('Category');
        }

        return $category;
    }

    /**
     * Fetches a Category for a mutation, enforcing PRD §67: the caller must
     * be the owner. Unlike findReadable, a mismatch here is a 403 — the
     * caller already knows the id exists (it's their own mutation attempt).
     */
    public function findOwned(string $categoryId, string $currentUserId): Category
    {
        $category = $this->categories->findById($categoryId);

        if ($category === null || $category->isDeleted()) {
            throw new NotFoundException('Category');
        }

        if (!$category->isOwnedBy($currentUserId)) {
            throw new ForbiddenException();
        }

        return $category;
    }
}
