<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

trait RequestBody
{
    /** @return array<string, mixed> */
    private function bodyAsArray(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            throw new ValidationException(['body' => 'must be a JSON object']);
        }

        return $body;
    }
}
