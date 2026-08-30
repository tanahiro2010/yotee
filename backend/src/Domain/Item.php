<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Server-side "Item" (PRD §44 `items`). `scheduleRule` is stored and passed
 * through as a plain array — its shape is validated against `scheduleType`
 * by Validation\ScheduleRuleValidation before it ever reaches here, and the
 * server never interprets it further (Occurrence expansion is the client's
 * job, see CLAUDE.md "Core architectural principle").
 */
final class Item
{
    public function __construct(
        public readonly string $id,
        public readonly string $categoryId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ScheduleType $scheduleType,
        public readonly array $scheduleRule,
        public readonly ?string $location,
        public readonly ?string $url,
        public readonly int $sortOrder,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'scheduleType' => $this->scheduleType->value,
            'scheduleRule' => $this->scheduleRule,
            'location' => $this->location,
            'url' => $this->url,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
            'deletedAt' => $this->deletedAt?->format(DATE_ATOM),
        ];
    }
}
