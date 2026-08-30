<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\DevicePlatform;
use App\Domain\Exception\ValidationException;

final class DeviceValidation
{
    private const PUSH_TOKEN_MAX_LENGTH = 512;

    /** @return array{platform: DevicePlatform, pushToken: string} */
    public static function validateRegister(array $body): array
    {
        $errors = [];

        $platformValue = $body['platform'] ?? null;
        if (!is_string($platformValue) || DevicePlatform::tryFrom($platformValue) === null) {
            $errors['platform'] = 'must be one of ios, android';
        }

        $pushToken = $body['pushToken'] ?? null;
        if (!is_string($pushToken) || trim($pushToken) === '') {
            $errors['pushToken'] = 'is required';
        } elseif (strlen($pushToken) > self::PUSH_TOKEN_MAX_LENGTH) {
            $errors['pushToken'] = 'is too long to be a valid push token';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'platform' => DevicePlatform::from($platformValue),
            'pushToken' => $pushToken,
        ];
    }
}
