<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Item;
use App\Repository\Contract\ItemRepositoryInterface;

final class InMemoryItemRepository implements ItemRepositoryInterface
{
    /** @var array<string, Item> */
    private array $items = [];

    public function findById(string $id): ?Item
    {
        return $this->items[$id] ?? null;
    }

    public function create(Item $item): Item
    {
        $this->items[$item->id] = $item;

        return $item;
    }

    public function save(Item $item): Item
    {
        $this->items[$item->id] = $item;

        return $item;
    }

    public function findByCategoryId(string $categoryId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (Item $i) => $i->categoryId === $categoryId && !$i->isDeleted(),
        ));
    }

    public function findUpdatedSince(array $categoryIds, ?\DateTimeImmutable $since, int $limit): array
    {
        $matches = array_values(array_filter(
            $this->items,
            static fn (Item $i) => in_array($i->categoryId, $categoryIds, true)
                && ($since === null || $i->updatedAt > $since),
        ));

        usort($matches, static fn (Item $a, Item $b) => $a->updatedAt <=> $b->updatedAt);

        return array_slice($matches, 0, $limit);
    }
}
