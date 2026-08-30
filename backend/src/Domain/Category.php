<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Server-side "List" (PRD §43 `categories`). Never call this a Category in
 * user-facing copy — that's the UI's job to translate, not this class's.
 */
final class Category
{
    public function __construct(
        public readonly string $id,
        public readonly string $ownerId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly CategoryVisibility $visibility,
        public readonly string $timezone,
        public readonly int $version,
        public readonly ?array $recommendedReminder,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function isOwnedBy(string $userId): bool
    {
        return hash_equals($this->ownerId, $userId);
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Every Item mutation under this Category must bump `version` in the
     * same transaction as the Item write (PRD §20, §37) — this is what
     * drives client delta-sync and the `category.updated` push signal.
     */
    public function withIncrementedVersion(\DateTimeImmutable $now): self
    {
        return new self(
            $this->id,
            $this->ownerId,
            $this->name,
            $this->description,
            $this->visibility,
            $this->timezone,
            $this->version + 1,
            $this->recommendedReminder,
            $this->createdAt,
            $now,
            $this->deletedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'name' => $this->name,
            'description' => $this->description,
            'visibility' => $this->visibility->value,
            'timezone' => $this->timezone,
            'version' => $this->version,
            'recommendedReminder' => $this->recommendedReminder,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
            'deletedAt' => $this->deletedAt?->format(DATE_ATOM),
        ];
    }
}
