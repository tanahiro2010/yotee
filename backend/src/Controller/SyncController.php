<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Category;
use App\Domain\Item;
use App\Domain\Subscription;
use App\Middleware\AuthMiddleware;
use App\Service\SyncService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SyncController
{
    use JsonResponder;

    public function __construct(private readonly SyncService $syncService)
    {
    }

    public function sync(Request $request, Response $response): Response
    {
        $cursor = $request->getQueryParams()['cursor'] ?? null;
        $result = $this->syncService->sync(AuthMiddleware::currentUserId($request), is_string($cursor) ? $cursor : null);

        return $this->json($response, [
            'categories' => array_map(static fn (Category $c) => $c->toArray(), $result['categories']),
            'items' => array_map(static fn (Item $i) => $i->toArray(), $result['items']),
            'subscriptions' => array_map(static fn (Subscription $s) => $s->toArray(), $result['subscriptions']),
            'nextCursor' => $result['nextCursor'],
        ]);
    }
}
