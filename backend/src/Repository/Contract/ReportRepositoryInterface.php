<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Report;

/** PRD §58 Public List Report — minimal moderation intake, no in-app review UI in MVP. */
interface ReportRepositoryInterface
{
    public function create(Report $report): Report;
}
