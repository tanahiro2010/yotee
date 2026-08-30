<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Exception\UnauthorizedException;
use App\Infrastructure\Auth\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Like AuthMiddleware, but a missing/invalid token means "anonymous", not a
 * 401 — for routes whose behaviour only *changes* when a caller is
 * identified (e.g. GET /categories/{id}, where an owner can see their own
 * `private` List but everyone else needs a 404, not a login prompt).
 */
final class OptionalAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (str_starts_with($header, 'Bearer ')) {
            try {
                $userId = $this->jwt->verifyAccessToken(substr($header, strlen('Bearer ')));
                $request = $request->withAttribute(AuthMiddleware::USER_ID_ATTRIBUTE, $userId);
            } catch (UnauthorizedException) {
                // Treat an expired/malformed token the same as no token at
                // all — this route never requires auth, so don't 401 here.
            }
        }

        return $handler->handle($request);
    }

    public static function currentUserId(ServerRequestInterface $request): ?string
    {
        $userId = $request->getAttribute(AuthMiddleware::USER_ID_ATTRIBUTE);

        return is_string($userId) ? $userId : null;
    }
}
