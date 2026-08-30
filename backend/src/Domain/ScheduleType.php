<?php

declare(strict_types=1);

namespace App\Domain;

/** Mirrors docs/api's `ScheduleType` — keep both in sync (PRD §8, §45). */
enum ScheduleType: string
{
    case Once = 'once';
    case Weekly = 'weekly';
    case MonthlyDay = 'monthly_day';
    case MonthlyNthWeekday = 'monthly_nth_weekday';
    case Yearly = 'yearly';
}
