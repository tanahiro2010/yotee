<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * PRD §46 `subscriptions`. Notification timing preferences are deliberately
 * absent — they live only in the device's local SQLite.
 */
final class Subscription
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $categoryId,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}
