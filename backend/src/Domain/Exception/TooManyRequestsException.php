<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TooManyRequestsException extends AppException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('TOO_MANY_REQUESTS', 'Rate limit exceeded', 429);
    }
}
