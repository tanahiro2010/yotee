<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Exception\TooManyRequestsException;
use App\Infrastructure\RateLimit\RateLimiterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PRD §66 "Rate Limit". Registered as global app-level middleware (see
 * public/index.php) so it wraps every request, including anonymous ones —
 * which means it runs before any route-group AuthMiddleware and keys by
 * remote IP in practice. It still prefers a `userId` request attribute if
 * one is already set, so a future per-route-group placement (deeper than
 * Auth, for tighter per-account limits on top of the IP-based floor) needs
 * no changes here.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterInterface $limiter,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $request->getAttribute(AuthMiddleware::USER_ID_ATTRIBUTE);
        $key = is_string($userId) ? "user:{$userId}" : 'ip:' . $this->clientIp($request);

        $result = $this->limiter->hit($key, $this->maxRequests, $this->windowSeconds);
        if (!$result->allowed) {
            throw new TooManyRequestsException($result->retryAfterSeconds);
        }

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        return is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : 'unknown';
    }
}
