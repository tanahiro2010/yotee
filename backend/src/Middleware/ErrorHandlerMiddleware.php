<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Exception\AppException;
use App\Domain\Exception\TooManyRequestsException;
use App\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * The single place that turns an exception into the uniform
 * `{ "error": { "code", "message" } }` envelope (PRD §52). Must be the
 * outermost middleware (added last in public/index.php) so it wraps every
 * route, every other middleware, and Slim's own routing/parsing errors.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly bool $debug,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (TooManyRequestsException $e) {
            return $this->render($e->httpStatus(), $e->errorCode(), $e->getMessage())
                ->withHeader('Retry-After', (string) $e->retryAfterSeconds);
        } catch (AppException $e) {
            return $this->render($e->httpStatus(), $e->errorCode(), $e->getMessage(), $this->extra($e));
        } catch (\Throwable $e) {
            // Anything else is a bug, not a client error — log the real
            // exception server-side but never leak internals to the client
            // (PRD §66 "Output Escape" applies just as much to error bodies).
            $this->logger->error('unhandled_exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $this->debug ? $e->getTraceAsString() : null,
            ]);

            $message = $this->debug ? $e->getMessage() : 'An unexpected error occurred';

            return $this->render(500, 'INTERNAL_ERROR', $message);
        }
    }

    private function extra(AppException $e): array
    {
        if ($e instanceof ValidationException) {
            return ['fieldErrors' => $e->fieldErrors()];
        }

        return [];
    }

    private function render(int $status, string $code, string $message, array $extra = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $body = ['error' => ['code' => $code, 'message' => $message, ...$extra]];
        $response->getBody()->write(json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
