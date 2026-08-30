<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Category;
use App\Repository\Contract\CategoryRepositoryInterface;

final class InMemoryCategoryRepository implements CategoryRepositoryInterface
{
    /** @var array<string, Category> */
    private array $categories = [];

    public function findById(string $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    public function create(Category $category): Category
    {
        $this->categories[$category->id] = $category;

        return $category;
    }

    public function save(Category $category): Category
    {
        $this->categories[$category->id] = $category;

        return $category;
    }

    public function findIdsOwnedBy(string $userId): array
    {
        // Deliberately not filtering isDeleted() — see the interface docblock.
        return array_values(array_map(
            static fn (Category $c) => $c->id,
            array_filter($this->categories, static fn (Category $c) => $c->ownerId === $userId),
        ));
    }

    public function findByIds(array $ids): array
    {
        return array_values(array_filter(
            $this->categories,
            fn (Category $c) => in_array($c->id, $ids, true) && !$c->isDeleted(),
        ));
    }

    public function searchPublic(string $query, ?string $cursor, int $limit): array
    {
        $matches = array_values(array_filter(
            $this->categories,
            static fn (Category $c) => $c->visibility->value === 'public'
                && !$c->isDeleted()
                && (str_contains($c->name, $query) || str_contains((string) $c->description, $query)),
        ));

        return ['items' => array_slice($matches, 0, $limit), 'nextCursor' => null];
    }

    public function findUpdatedSince(array $categoryIds, ?\DateTimeImmutable $since, int $limit): array
    {
        $matches = array_values(array_filter(
            $this->categories,
            static fn (Category $c) => in_array($c->id, $categoryIds, true)
                && ($since === null || $c->updatedAt > $since),
        ));

        usort($matches, static fn (Category $a, Category $b) => $a->updatedAt <=> $b->updatedAt);

        return array_slice($matches, 0, $limit);
    }
}
