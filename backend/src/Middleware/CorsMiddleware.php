<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Native mobile clients don't send an `Origin` header, so this mostly
 * matters for the web preview / deep-link landing page (PRD §56, CLAUDE.md
 * "Deep Link" — this is the one narrow web surface MVP scope still needs).
 * Origins are matched against an explicit allow-list — never reflected
 * wholesale — so this can't be turned into an open CORS policy by a stray `*`.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /** @param string[] $allowedOrigins */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly array $allowedOrigins,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = $origin !== '' && in_array($origin, $this->allowedOrigins, true);

        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(204);
        } else {
            $response = $handler->handle($request);
        }

        if (!$allowed) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
