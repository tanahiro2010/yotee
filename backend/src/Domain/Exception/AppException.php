<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Base for every exception that should reach the client as the uniform
 * `{ "error": { "code", "message" } }` envelope (PRD §52). Caught centrally
 * by Middleware\ErrorHandlerMiddleware — Controllers never format errors
 * themselves.
 */
abstract class AppException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $httpStatus,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
