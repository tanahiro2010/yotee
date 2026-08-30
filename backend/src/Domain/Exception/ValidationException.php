<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ValidationException extends AppException
{
    /** @param array<string, string> $fieldErrors field name => human-readable reason */
    public function __construct(private readonly array $fieldErrors, string $message = 'Validation failed')
    {
        parent::__construct('VALIDATION_ERROR', $message, 422);
    }

    /** @return array<string, string> */
    public function fieldErrors(): array
    {
        return $this->fieldErrors;
    }
}
