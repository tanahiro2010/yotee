<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** e.g. subscribing twice — `UNIQUE(user_id, category_id)` (PRD §46). */
final class ConflictException extends AppException
{
    public function __construct(string $message, string $errorCode = 'CONFLICT')
    {
        parent::__construct($errorCode, $message, 409);
    }
}
