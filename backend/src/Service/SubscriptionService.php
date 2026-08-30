<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Exception\ConflictException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Subscription;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\SubscriptionRepositoryInterface;

final class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly CategoryRepositoryInterface $categories,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function subscribe(string $categoryId, string $userId): Subscription
    {
        // Reuses the same visibility rule as reading a Category (PRD §17):
        // you can only subscribe to something you're allowed to see. A
        // private Category you don't own 404s here exactly like it would on
        // GET, rather than confirming it exists via a different error.
        $category = $this->categories->findById($categoryId);
        if ($category === null || $category->isDeleted() ||
            ($category->visibility->value === 'private' && !$category->isOwnedBy($userId))
        ) {
            throw new NotFoundException('Category');
        }

        if ($this->subscriptions->find($userId, $categoryId) !== null) {
            throw new ConflictException('Already subscribed to this Category', 'ALREADY_SUBSCRIBED');
        }

        return $this->subscriptions->create(new Subscription(
            id: $this->uuids->generate(),
            userId: $userId,
            categoryId: $categoryId,
            createdAt: new \DateTimeImmutable(),
        ));
    }

    public function unsubscribe(string $categoryId, string $userId): void
    {
        if ($this->subscriptions->find($userId, $categoryId) === null) {
            throw new NotFoundException('Subscription');
        }

        $this->subscriptions->delete($userId, $categoryId);
    }

    /** @return array<array{subscription: Subscription, category: \App\Domain\Category}> */
    public function listForUser(string $userId): array
    {
        $subscriptions = $this->subscriptions->findAllForUser($userId);
        $categoriesById = [];
        foreach ($this->categories->findByIds(array_map(static fn (Subscription $s) => $s->categoryId, $subscriptions)) as $category) {
            $categoriesById[$category->id] = $category;
        }

        $result = [];
        foreach ($subscriptions as $subscription) {
            $category = $categoriesById[$subscription->categoryId] ?? null;
            // A Category can vanish from under a Subscription only through
            // hard deletion at the DB level, which this system never does
            // (soft delete only) — skip defensively rather than error if it
            // ever happens via manual intervention.
            if ($category !== null) {
                $result[] = ['subscription' => $subscription, 'category' => $category];
            }
        }

        return $result;
    }
}
