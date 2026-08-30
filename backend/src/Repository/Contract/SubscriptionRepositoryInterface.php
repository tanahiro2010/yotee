<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Subscription;

interface SubscriptionRepositoryInterface
{
    public function find(string $userId, string $categoryId): ?Subscription;

    public function create(Subscription $subscription): Subscription;

    /** Hard delete — `subscriptions` has no `deleted_at` column (PRD §46). */
    public function delete(string $userId, string $categoryId): void;

    /** @return string[] Category ids this user currently subscribes to. */
    public function findCategoryIdsSubscribedBy(string $userId): array;

    /** @return string[] User ids currently subscribed to this Category (for push fan-out). */
    public function findUserIdsSubscribedTo(string $categoryId): array;

    /** @return Subscription[] All current subscriptions for this user, newest first. */
    public function findAllForUser(string $userId): array;

    /**
     * Subscriptions created after $since (or all, if null). Unsubscribing
     * hard-deletes the row, so — unlike Categories/Items — a removed
     * Subscription cannot be reconciled via this delta alone; the client
     * still gets it via a Category dropping out of its owned/subscribed set.
     *
     * @return Subscription[]
     */
    public function findCreatedSince(string $userId, ?\DateTimeImmutable $since, int $limit): array;
}
