<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Service\ReportService;
use App\Validation\ReportValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ReportController
{
    use JsonResponder;
    use RequestBody;

    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function report(Request $request, Response $response, array $args): Response
    {
        $validated = ReportValidation::validateCreate($this->bodyAsArray($request));
        $this->reportService->report(
            $args['categoryId'],
            AuthMiddleware::currentUserId($request),
            $validated['reason'],
            $validated['detail'],
        );

        return $this->noContent($response);
    }
}
