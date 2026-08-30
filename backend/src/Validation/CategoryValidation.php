<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\CategoryVisibility;
use App\Domain\Exception\ValidationException;

final class CategoryValidation
{
    private const NAME_MAX_LENGTH = 100;
    private const DESCRIPTION_MAX_LENGTH = 2000;

    /** @return array{name: string, description: ?string, visibility: CategoryVisibility, timezone: string, recommendedReminder: ?array} */
    public static function validateCreate(array $body): array
    {
        $errors = [];

        $name = self::requiredString($body, 'name', self::NAME_MAX_LENGTH, $errors);
        $description = self::optionalString($body, 'description', self::DESCRIPTION_MAX_LENGTH, $errors);
        $visibility = self::requiredVisibility($body, $errors);
        $timezone = self::requiredTimezone($body, $errors);
        $recommendedReminder = self::optionalRecommendedReminder($body, $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'name' => $name,
            'description' => $description,
            'visibility' => $visibility,
            'timezone' => $timezone,
            'recommendedReminder' => $recommendedReminder,
        ];
    }

    /** @return array{name?: string, description?: ?string, visibility?: CategoryVisibility, timezone?: string, recommendedReminder?: ?array} */
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
        if (array_key_exists('visibility', $body)) {
            $result['visibility'] = self::requiredVisibility($body, $errors);
        }
        if (array_key_exists('timezone', $body)) {
            $result['timezone'] = self::requiredTimezone($body, $errors);
        }
        if (array_key_exists('recommendedReminder', $body)) {
            $result['recommendedReminder'] = self::optionalRecommendedReminder($body, $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
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

    private static function requiredVisibility(array $body, array &$errors): CategoryVisibility
    {
        $value = $body['visibility'] ?? null;
        if (!is_string($value) || CategoryVisibility::tryFrom($value) === null) {
            $errors['visibility'] = 'must be one of private, unlisted, public';

            return CategoryVisibility::Private;
        }

        return CategoryVisibility::from($value);
    }

    private static function requiredTimezone(array $body, array &$errors): string
    {
        $value = $body['timezone'] ?? null;
        if (!is_string($value) || !in_array($value, \DateTimeZone::listIdentifiers(), true)) {
            $errors['timezone'] = 'must be a valid IANA timezone identifier, e.g. Asia/Tokyo';

            return '';
        }

        return $value;
    }

    private static function optionalRecommendedReminder(array $body, array &$errors): ?array
    {
        $value = $body['recommendedReminder'] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            $errors['recommendedReminder'] = 'must be an object';

            return null;
        }

        $preset = $value['preset'] ?? null;
        if (!in_array($preset, ['night_before', 'same_day_morning', 'custom'], true)) {
            $errors['recommendedReminder.preset'] = 'must be one of night_before, same_day_morning, custom';

            return null;
        }

        $customMinutesBefore = $value['customMinutesBefore'] ?? null;
        if ($preset === 'custom') {
            if (!is_int($customMinutesBefore) || $customMinutesBefore < 1) {
                $errors['recommendedReminder.customMinutesBefore'] = 'is required and must be a positive integer when preset is custom';

                return null;
            }

            return ['preset' => $preset, 'customMinutesBefore' => $customMinutesBefore];
        }

        return ['preset' => $preset];
    }
}
