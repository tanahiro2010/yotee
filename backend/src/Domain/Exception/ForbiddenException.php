<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown whenever `resource.category.owner_id !== currentUser.id` on a
 * mutating endpoint (PRD §67). Never trust an owner id sent by the client —
 * this must be raised from a server-side comparison every time.
 */
final class ForbiddenException extends AppException
{
    public function __construct(string $message = 'You do not have permission to perform this action')
    {
        parent::__construct('FORBIDDEN', $message, 403);
    }
}
