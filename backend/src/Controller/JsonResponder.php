<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * Controllers only ever build a response by handing data to this — success
 * responses are the resource directly, never wrapped (PRD §52), and this is
 * the one place that turns a PHP array into that JSON body.
 */
trait JsonResponder
{
    private function json(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    private function noContent(ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(204);
    }
}
