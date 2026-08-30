<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class NotFoundException extends AppException
{
    public function __construct(string $resource, string $errorCode = 'RESOURCE_NOT_FOUND')
    {
        parent::__construct($errorCode, "{$resource} not found", 404);
    }
}
