<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\Exception\ValidationException;
use App\Domain\ReportReason;

final class ReportValidation
{
    private const DETAIL_MAX_LENGTH = 1000;

    /** @return array{reason: ReportReason, detail: ?string} */
    public static function validateCreate(array $body): array
    {
        $errors = [];

        $reasonValue = $body['reason'] ?? null;
        if (!is_string($reasonValue) || ReportReason::tryFrom($reasonValue) === null) {
            $errors['reason'] = 'must be one of spam, misinformation, impersonation, inappropriate, other';
        }

        $detail = $body['detail'] ?? null;
        if ($detail !== null && (!is_string($detail) || mb_strlen($detail) > self::DETAIL_MAX_LENGTH)) {
            $errors['detail'] = 'must be a string of at most ' . self::DETAIL_MAX_LENGTH . ' characters';
        }

        if ($reasonValue === 'other' && (!is_string($detail) || trim($detail) === '')) {
            $errors['detail'] = 'is required when reason is other';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'reason' => ReportReason::from($reasonValue),
            'detail' => $detail,
        ];
    }
}
