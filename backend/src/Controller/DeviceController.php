<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Service\DeviceService;
use App\Validation\DeviceValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeviceController
{
    use JsonResponder;
    use RequestBody;

    public function __construct(private readonly DeviceService $deviceService)
    {
    }

    public function register(Request $request, Response $response): Response
    {
        $validated = DeviceValidation::validateRegister($this->bodyAsArray($request));
        $device = $this->deviceService->register(
            AuthMiddleware::currentUserId($request),
            $validated['platform'],
            $validated['pushToken'],
        );

        return $this->json($response, $device->toArray(), 201);
    }

    public function unregister(Request $request, Response $response, array $args): Response
    {
        $this->deviceService->unregister($args['id'], AuthMiddleware::currentUserId($request));

        return $this->noContent($response);
    }
}
