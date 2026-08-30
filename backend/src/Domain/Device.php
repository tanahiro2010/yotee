<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * PRD §47 `devices`. Its only server-side job is receiving `category.updated`
 * pushes — it never carries reminder payloads (CLAUDE.md core principle).
 */
final class Device
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly DevicePlatform $platform,
        public readonly string $pushToken,
        public readonly ?\DateTimeImmutable $lastSeenAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform->value,
            'pushToken' => $this->pushToken,
            'lastSeenAt' => $this->lastSeenAt?->format(DATE_ATOM),
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
