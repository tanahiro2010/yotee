<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Category;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Item;
use App\Infrastructure\Push\PushNotificationDispatcherInterface;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\DeviceRepositoryInterface;
use App\Repository\Contract\ItemRepositoryInterface;
use App\Repository\Contract\SubscriptionRepositoryInterface;

/**
 * Item change + Category version bump + update-push always happen together,
 * in this Service, exactly once (PRD §37) — Controllers never call the
 * Category/Subscription/Device repositories directly to reassemble this.
 */
final class ItemService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
        private readonly CategoryRepositoryInterface $categories,
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly DeviceRepositoryInterface $devices,
        private readonly PushNotificationDispatcherInterface $pushDispatcher,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function create(string $categoryId, string $currentUserId, array $validated): Item
    {
        $category = $this->findOwnedCategory($categoryId, $currentUserId);

        $now = new \DateTimeImmutable();
        $item = $this->items->create(new Item(
            id: $this->uuids->generate(),
            categoryId: $category->id,
            name: $validated['name'],
            description: $validated['description'],
            scheduleType: $validated['scheduleType'],
            scheduleRule: $validated['scheduleRule'],
            location: $validated['location'],
            url: $validated['url'],
            sortOrder: $validated['sortOrder'],
            createdAt: $now,
            updatedAt: $now,
        ));

        $this->bumpVersionAndNotify($category);

        return $item;
    }

    public function update(string $itemId, string $currentUserId, array $patch): Item
    {
        [$item, $category] = $this->findOwnedItem($itemId, $currentUserId);

        $updated = new Item(
            id: $item->id,
            categoryId: $item->categoryId,
            name: $patch['name'] ?? $item->name,
            description: array_key_exists('description', $patch) ? $patch['description'] : $item->description,
            scheduleType: $patch['scheduleType'] ?? $item->scheduleType,
            scheduleRule: $patch['scheduleRule'] ?? $item->scheduleRule,
            location: array_key_exists('location', $patch) ? $patch['location'] : $item->location,
            url: array_key_exists('url', $patch) ? $patch['url'] : $item->url,
            sortOrder: $patch['sortOrder'] ?? $item->sortOrder,
            createdAt: $item->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
        $saved = $this->items->save($updated);

        $this->bumpVersionAndNotify($category);

        return $saved;
    }

    public function delete(string $itemId, string $currentUserId): void
    {
        [$item, $category] = $this->findOwnedItem($itemId, $currentUserId);

        $this->items->save(new Item(
            id: $item->id,
            categoryId: $item->categoryId,
            name: $item->name,
            description: $item->description,
            scheduleType: $item->scheduleType,
            scheduleRule: $item->scheduleRule,
            location: $item->location,
            url: $item->url,
            sortOrder: $item->sortOrder,
            createdAt: $item->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: new \DateTimeImmutable(),
        ));

        $this->bumpVersionAndNotify($category);
    }

    private function bumpVersionAndNotify(Category $category): void
    {
        $updatedCategory = $this->categories->save($category->withIncrementedVersion(new \DateTimeImmutable()));

        $subscriberIds = $this->subscriptions->findUserIdsSubscribedTo($category->id);
        $subscriberIds[] = $category->ownerId;
        $tokens = $this->devices->findPushTokensForUsers(array_unique($subscriberIds));

        $this->pushDispatcher->dispatchCategoryUpdated($updatedCategory->id, $updatedCategory->version, $tokens);
    }

    /** @return array{0: Item, 1: Category} */
    private function findOwnedItem(string $itemId, string $currentUserId): array
    {
        $item = $this->items->findById($itemId);
        if ($item === null || $item->isDeleted()) {
            throw new NotFoundException('Item');
        }

        $category = $this->categories->findById($item->categoryId);
        if ($category === null || $category->isDeleted()) {
            throw new NotFoundException('Item');
        }

        if (!$category->isOwnedBy($currentUserId)) {
            throw new ForbiddenException();
        }

        return [$item, $category];
    }

    private function findOwnedCategory(string $categoryId, string $currentUserId): Category
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
