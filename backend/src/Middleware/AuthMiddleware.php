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
 * Verifies the `Authorization: Bearer <token>` header and attaches the
 * caller's user id to the request as the `userId` attribute. Applied only to
 * route groups that require auth (see routes/*.php) — never registered
 * globally, since search/get-public-Category/login/refresh stay anonymous.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public const USER_ID_ATTRIBUTE = 'userId';

    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException('Missing bearer token');
        }

        $token = substr($header, strlen('Bearer '));
        $userId = $this->jwt->verifyAccessToken($token);

        return $handler->handle($request->withAttribute(self::USER_ID_ATTRIBUTE, $userId));
    }

    public static function currentUserId(ServerRequestInterface $request): string
    {
        $userId = $request->getAttribute(self::USER_ID_ATTRIBUTE);
        if (!is_string($userId)) {
            // Only reachable if a route reads the attribute without being
            // behind AuthMiddleware — a wiring bug, not a client error.
            throw new \LogicException('Route requires AuthMiddleware but none ran');
        }

        return $userId;
    }
}
