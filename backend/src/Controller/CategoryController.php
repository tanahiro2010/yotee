<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Category;
use App\Domain\Item;
use App\Middleware\AuthMiddleware;
use App\Middleware\OptionalAuthMiddleware;
use App\Service\CategoryService;
use App\Validation\CategoryValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CategoryController
{
    use JsonResponder;
    use RequestBody;

    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function search(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $result = $this->categoryService->search(
            (string) ($params['q'] ?? ''),
            isset($params['cursor']) ? (string) $params['cursor'] : null,
            isset($params['limit']) ? (int) $params['limit'] : null,
        );

        return $this->json($response, [
            'items' => array_map(static fn (Category $c) => $c->toArray(), $result['items']),
            'nextCursor' => $result['nextCursor'],
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $validated = CategoryValidation::validateCreate($this->bodyAsArray($request));
        $category = $this->categoryService->create(AuthMiddleware::currentUserId($request), $validated);

        return $this->json($response, $category->toArray(), 201);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $detail = $this->categoryService->getDetail($args['id'], OptionalAuthMiddleware::currentUserId($request));

        return $this->json($response, [
            ...$detail['category']->toArray(),
            'items' => array_map(static fn (Item $i) => $i->toArray(), $detail['items']),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $patch = CategoryValidation::validateUpdate($this->bodyAsArray($request));
        $category = $this->categoryService->update($args['id'], AuthMiddleware::currentUserId($request), $patch);

        return $this->json($response, $category->toArray());
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->categoryService->delete($args['id'], AuthMiddleware::currentUserId($request));

        return $this->noContent($response);
    }
}
