<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\AuthProvider;
use App\Domain\Exception\ValidationException;

final class AuthValidation
{
    /** @return array{provider: AuthProvider, idToken: string} */
    public static function validateLogin(array $body): array
    {
        $errors = [];

        $providerValue = $body['provider'] ?? null;
        if (!is_string($providerValue) || AuthProvider::tryFrom($providerValue) === null) {
            $errors['provider'] = 'must be one of google, apple';
        }

        $idToken = $body['idToken'] ?? null;
        if (!is_string($idToken) || trim($idToken) === '') {
            $errors['idToken'] = 'is required';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['provider' => AuthProvider::from($providerValue), 'idToken' => $idToken];
    }

    /** @return array{refreshToken: string} */
    public static function validateRefreshToken(array $body): array
    {
        $refreshToken = $body['refreshToken'] ?? null;
        if (!is_string($refreshToken) || trim($refreshToken) === '') {
            throw new ValidationException(['refreshToken' => 'is required']);
        }

        return ['refreshToken' => $refreshToken];
    }
}
