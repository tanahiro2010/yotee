<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Exception\UnauthorizedException;
use App\Middleware\AuthMiddleware;
use App\Repository\Contract\UserRepositoryInterface;
use App\Service\AuthService;
use App\Validation\AuthValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    use JsonResponder;
    use RequestBody;

    public function __construct(
        private readonly AuthService $authService,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function login(Request $request, Response $response): Response
    {
        $validated = AuthValidation::validateLogin($this->bodyAsArray($request));
        $result = $this->authService->login($validated['provider'], $validated['idToken']);

        return $this->json($response, [
            'accessToken' => $result['accessToken'],
            'refreshToken' => $result['refreshToken'],
            'expiresIn' => $result['expiresIn'],
            'user' => $result['user']->toArray(),
        ]);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $validated = AuthValidation::validateRefreshToken($this->bodyAsArray($request));
        $result = $this->authService->refresh($validated['refreshToken']);

        return $this->json($response, $result);
    }

    public function logout(Request $request, Response $response): Response
    {
        $validated = AuthValidation::validateRefreshToken($this->bodyAsArray($request));
        $this->authService->logout(AuthMiddleware::currentUserId($request), $validated['refreshToken']);

        return $this->noContent($response);
    }

    public function me(Request $request, Response $response): Response
    {
        $userId = AuthMiddleware::currentUserId($request);
        $user = $this->users->findById($userId);
        if ($user === null) {
            // The access token verified fine but the user is gone — treat it
            // as unauthenticated rather than leaking a 500.
            throw new UnauthorizedException('User no longer exists');
        }

        return $this->json($response, $user->toArray());
    }
}
