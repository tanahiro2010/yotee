<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\Exception\ValidationException;
use App\Domain\ScheduleType;

final class ItemValidation
{
    private const NAME_MAX_LENGTH = 100;
    private const DESCRIPTION_MAX_LENGTH = 2000;
    private const LOCATION_MAX_LENGTH = 200;

    /**
     * @return array{name: string, description: ?string, scheduleType: ScheduleType,
     *     scheduleRule: array, location: ?string, url: ?string, sortOrder: int}
     */
    public static function validateCreate(array $body): array
    {
        $errors = [];

        $name = self::requiredString($body, 'name', self::NAME_MAX_LENGTH, $errors);
        $description = self::optionalString($body, 'description', self::DESCRIPTION_MAX_LENGTH, $errors);
        $location = self::optionalString($body, 'location', self::LOCATION_MAX_LENGTH, $errors);
        $url = self::optionalUrl($body, $errors);
        $sortOrder = self::optionalSortOrder($body, $errors);

        $scheduleRule = $body['scheduleRule'] ?? null;
        if (!is_array($scheduleRule)) {
            $errors['scheduleRule'] = 'is required';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Validated separately so its own field-level errors stay specific
        // (scheduleRule.time, scheduleRule.weekdays, ...) instead of being
        // flattened into a single generic "scheduleRule" error.
        $validatedRule = ScheduleRuleValidation::validate($scheduleRule);

        return [
            'name' => $name,
            'description' => $description,
            'scheduleType' => $validatedRule['scheduleType'],
            'scheduleRule' => $validatedRule['scheduleRule'],
            'location' => $location,
            'url' => $url,
            'sortOrder' => $sortOrder ?? 0,
        ];
    }

    /**
     * @return array{name?: string, description?: ?string, scheduleType?: ScheduleType,
     *     scheduleRule?: array, location?: ?string, url?: ?string, sortOrder?: int}
     */
    public static function validateUpdate(array $body): array
    {
        $errors = [];
        $result = [];

        if (array_key_exists('name', $body)) {
            $result['name'] = self::requiredString($body, 'name', self::NAME_MAX_LENGTH, $errors);
        }
        if (array_key_exists('description', $body)) {
            $result['description'] = self::optionalString($body, 'description', self::DESCRIPTION_MAX_LENGTH, $errors);
        }
        if (array_key_exists('location', $body)) {
            $result['location'] = self::optionalString($body, 'location', self::LOCATION_MAX_LENGTH, $errors);
        }
        if (array_key_exists('url', $body)) {
            $result['url'] = self::optionalUrl($body, $errors);
        }
        if (array_key_exists('sortOrder', $body)) {
            $result['sortOrder'] = self::optionalSortOrder($body, $errors) ?? 0;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if (array_key_exists('scheduleRule', $body)) {
            $scheduleRule = $body['scheduleRule'];
            if (!is_array($scheduleRule)) {
                throw new ValidationException(['scheduleRule' => 'must be an object']);
            }

            $validatedRule = ScheduleRuleValidation::validate($scheduleRule);
            $result['scheduleType'] = $validatedRule['scheduleType'];
            $result['scheduleRule'] = $validatedRule['scheduleRule'];
        }

        return $result;
    }

    private static function requiredString(array $body, string $field, int $maxLength, array &$errors): string
    {
        $value = $body[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            $errors[$field] = 'is required';

            return '';
        }
        if (mb_strlen($value) > $maxLength) {
            $errors[$field] = "must be at most {$maxLength} characters";
        }

        return $value;
    }

    private static function optionalString(array $body, string $field, int $maxLength, array &$errors): ?string
    {
        $value = $body[$field] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            $errors[$field] = 'must be a string';

            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            $errors[$field] = "must be at most {$maxLength} characters";
        }

        return $value;
    }

    /** PRD §66 "URL Validation" — restrict to http(s) so a stored value can never become a `javascript:`/`file:` link client-side. */
    private static function optionalUrl(array $body, array &$errors): ?string
    {
        $value = $body['url'] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $value)) {
            $errors['url'] = 'must be a valid http:// or https:// URL';

            return null;
        }

        return $value;
    }

    private static function optionalSortOrder(array $body, array &$errors): ?int
    {
        $value = $body['sortOrder'] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_int($value) || $value < 0) {
            $errors['sortOrder'] = 'must be a non-negative integer';

            return null;
        }

        return $value;
    }
}
