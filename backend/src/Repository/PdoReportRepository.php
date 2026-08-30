<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Report;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\ReportRepositoryInterface;

final class PdoReportRepository implements ReportRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function create(Report $report): Report
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reports (id, category_id, reporter_user_id, reason, detail, created_at)
             VALUES (:id, :category_id, :reporter_user_id, :reason, :detail, :created_at)'
        );
        $stmt->execute([
            'id' => $report->id,
            'category_id' => $report->categoryId,
            'reporter_user_id' => $report->reporterUserId,
            'reason' => $report->reason->value,
            'detail' => $report->detail,
            'created_at' => DateTimeCodec::toDb($report->createdAt),
        ]);

        return $report;
    }
}
