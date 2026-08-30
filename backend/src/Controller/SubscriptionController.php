<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Service\SubscriptionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SubscriptionController
{
    use JsonResponder;

    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    public function subscribe(Request $request, Response $response, array $args): Response
    {
        $subscription = $this->subscriptionService->subscribe($args['categoryId'], AuthMiddleware::currentUserId($request));

        return $this->json($response, $subscription->toArray(), 201);
    }

    public function unsubscribe(Request $request, Response $response, array $args): Response
    {
        $this->subscriptionService->unsubscribe($args['categoryId'], AuthMiddleware::currentUserId($request));

        return $this->noContent($response);
    }

    public function list(Request $request, Response $response): Response
    {
        $rows = $this->subscriptionService->listForUser(AuthMiddleware::currentUserId($request));

        return $this->json($response, array_map(
            static fn (array $row) => [...$row['subscription']->toArray(), 'category' => $row['category']->toArray()],
            $rows,
        ));
    }
}
