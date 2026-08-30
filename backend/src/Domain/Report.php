<?php

declare(strict_types=1);

namespace App\Domain;

final class Report
{
    public function __construct(
        public readonly string $id,
        public readonly string $categoryId,
        public readonly string $reporterUserId,
        public readonly ReportReason $reason,
        public readonly ?string $detail,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
