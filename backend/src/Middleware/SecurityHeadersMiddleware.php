<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Baseline hardening headers for a pure JSON API (PRD §66 "HTTPS Only").
 * HSTS assumes TLS termination happens in front of this app (load balancer /
 * reverse proxy) — it's a no-op, not a guarantee, if PHP itself serves plain
 * HTTP; enforce the redirect at that layer, not here.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            // Every response is JSON; there is no HTML surface for a CSP to
            // protect, so this simply forbids one from ever being rendered.
            ->withHeader('Content-Security-Policy', "default-src 'none'");
    }
}
