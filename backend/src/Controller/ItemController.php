<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Service\ItemService;
use App\Validation\ItemValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ItemController
{
    use JsonResponder;
    use RequestBody;

    public function __construct(private readonly ItemService $itemService)
    {
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $validated = ItemValidation::validateCreate($this->bodyAsArray($request));
        $item = $this->itemService->create($args['categoryId'], AuthMiddleware::currentUserId($request), $validated);

        return $this->json($response, $item->toArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $patch = ItemValidation::validateUpdate($this->bodyAsArray($request));
        $item = $this->itemService->update($args['itemId'], AuthMiddleware::currentUserId($request), $patch);

        return $this->json($response, $item->toArray());
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->itemService->delete($args['itemId'], AuthMiddleware::currentUserId($request));

        return $this->noContent($response);
    }
}
