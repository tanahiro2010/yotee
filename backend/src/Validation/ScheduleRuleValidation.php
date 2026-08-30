<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\Exception\ValidationException;
use App\Domain\ScheduleType;

/**
 * Validates the shape of `scheduleRule` against `scheduleType` (PRD §8, §45).
 * This is the server-side gate that keeps malformed rules out of the JSON
 * column — the *meaning* of a rule (occurrence expansion) is still entirely
 * the client's job, per CLAUDE.md's core architectural principle.
 */
final class ScheduleRuleValidation
{
    private const TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /**
     * @param array<string, mixed> $rule
     * @return array{scheduleType: ScheduleType, scheduleRule: array<string, mixed>}
     */
    public static function validate(array $rule): array
    {
        $type = $rule['scheduleType'] ?? null;
        if (!is_string($type) || ScheduleType::tryFrom($type) === null) {
            throw new ValidationException(['scheduleRule.scheduleType' => 'must be one of once, weekly, monthly_day, monthly_nth_weekday, yearly']);
        }

        $scheduleType = ScheduleType::from($type);

        $errors = match ($scheduleType) {
            ScheduleType::Once => self::validateOnce($rule),
            ScheduleType::Weekly => self::validateWeekly($rule),
            ScheduleType::MonthlyDay => self::validateMonthlyDay($rule),
            ScheduleType::MonthlyNthWeekday => self::validateMonthlyNthWeekday($rule),
            ScheduleType::Yearly => self::validateYearly($rule),
        };

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['scheduleType' => $scheduleType, 'scheduleRule' => $rule];
    }

    /** @return array<string, string> */
    private static function validateOnce(array $rule): array
    {
        $at = $rule['at'] ?? null;
        if (!is_string($at) || \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $at) === false) {
            return ['scheduleRule.at' => 'must be an ISO-8601 datetime with a UTC offset, e.g. 2026-09-30T18:00:00+09:00'];
        }

        return [];
    }

    /** @return array<string, string> */
    private static function validateWeekly(array $rule): array
    {
        $errors = [];

        $weekdays = $rule['weekdays'] ?? null;
        if (!is_array($weekdays) || $weekdays === [] || !self::allInRange($weekdays, 0, 6)) {
            $errors['scheduleRule.weekdays'] = 'must be a non-empty array of integers 0 (Sunday) through 6 (Saturday)';
        }

        $errors = array_merge($errors, self::validateTime($rule));

        return $errors;
    }

    /** @return array<string, string> */
    private static function validateMonthlyDay(array $rule): array
    {
        $errors = self::validateIntInRange($rule, 'day', 1, 31);

        return array_merge($errors, self::validateTime($rule));
    }

    /** @return array<string, string> */
    private static function validateMonthlyNthWeekday(array $rule): array
    {
        $errors = self::validateIntInRange($rule, 'nth', 1, 5);
        $errors = array_merge($errors, self::validateIntInRange($rule, 'weekday', 0, 6));

        return array_merge($errors, self::validateTime($rule));
    }

    /** @return array<string, string> */
    private static function validateYearly(array $rule): array
    {
        $errors = self::validateIntInRange($rule, 'month', 1, 12);
        $errors = array_merge($errors, self::validateIntInRange($rule, 'day', 1, 31));

        return array_merge($errors, self::validateTime($rule));
    }

    /** @return array<string, string> */
    private static function validateTime(array $rule): array
    {
        $time = $rule['time'] ?? null;
        if (!is_string($time) || preg_match(self::TIME_PATTERN, $time) !== 1) {
            return ['scheduleRule.time' => 'must be a local time in HH:MM 24-hour format'];
        }

        return [];
    }

    /** @return array<string, string> */
    private static function validateIntInRange(array $rule, string $field, int $min, int $max): array
    {
        $value = $rule[$field] ?? null;
        if (!is_int($value) || $value < $min || $value > $max) {
            return ["scheduleRule.{$field}" => "must be an integer between {$min} and {$max}"];
        }

        return [];
    }

    private static function allInRange(array $values, int $min, int $max): bool
    {
        foreach ($values as $value) {
            if (!is_int($value) || $value < $min || $value > $max) {
                return false;
            }
        }

        return true;
    }
}
