<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Exception\NotFoundException;
use App\Domain\Report;
use App\Domain\ReportReason;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\ReportRepositoryInterface;

final class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly CategoryRepositoryInterface $categories,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function report(string $categoryId, string $reporterUserId, ReportReason $reason, ?string $detail): Report
    {
        $category = $this->categories->findById($categoryId);
        if ($category === null || $category->isDeleted() || $category->visibility->value === 'private') {
            throw new NotFoundException('Category');
        }

        return $this->reports->create(new Report(
            id: $this->uuids->generate(),
            categoryId: $categoryId,
            reporterUserId: $reporterUserId,
            reason: $reason,
            detail: $detail,
            createdAt: new \DateTimeImmutable(),
        ));
    }
}
