<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Subscription;
use App\Repository\Contract\SubscriptionRepositoryInterface;

final class InMemorySubscriptionRepository implements SubscriptionRepositoryInterface
{
    /** @var array<string, Subscription> keyed by "userId:categoryId" */
    private array $subscriptions = [];

    public function find(string $userId, string $categoryId): ?Subscription
    {
        return $this->subscriptions["{$userId}:{$categoryId}"] ?? null;
    }

    public function create(Subscription $subscription): Subscription
    {
        $this->subscriptions["{$subscription->userId}:{$subscription->categoryId}"] = $subscription;

        return $subscription;
    }

    public function delete(string $userId, string $categoryId): void
    {
        unset($this->subscriptions["{$userId}:{$categoryId}"]);
    }

    public function findCategoryIdsSubscribedBy(string $userId): array
    {
        return array_values(array_map(
            static fn (Subscription $s) => $s->categoryId,
            array_filter($this->subscriptions, static fn (Subscription $s) => $s->userId === $userId),
        ));
    }

    public function findUserIdsSubscribedTo(string $categoryId): array
    {
        return array_values(array_map(
            static fn (Subscription $s) => $s->userId,
            array_filter($this->subscriptions, static fn (Subscription $s) => $s->categoryId === $categoryId),
        ));
    }

    public function findAllForUser(string $userId): array
    {
        return array_values(array_filter($this->subscriptions, static fn (Subscription $s) => $s->userId === $userId));
    }

    public function findCreatedSince(string $userId, ?\DateTimeImmutable $since, int $limit): array
    {
        $matches = array_values(array_filter(
            $this->subscriptions,
            static fn (Subscription $s) => $s->userId === $userId && ($since === null || $s->createdAt > $since),
        ));

        usort($matches, static fn (Subscription $a, Subscription $b) => $a->createdAt <=> $b->createdAt);

        return array_slice($matches, 0, $limit);
    }
}
