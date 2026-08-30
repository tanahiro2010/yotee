<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct('UNAUTHORIZED', $message, 401);
    }
}
