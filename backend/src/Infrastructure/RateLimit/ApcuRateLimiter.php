<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

/**
 * Fixed-window counter backed by APCu shared memory — no extra
 * infrastructure to run, and a hit costs a single shared-memory op instead
 * of a network round trip (PRD §65 Performance). This only limits per-host:
 * once the API runs behind more than one PHP-FPM host, replace this with a
 * Redis-backed implementation of the same interface (no caller changes).
 */
final class ApcuRateLimiter implements RateLimiterInterface
{
    public function __construct()
    {
        if (!extension_loaded('apcu')) {
            throw new \RuntimeException('ApcuRateLimiter requires the apcu extension');
        }
    }

    public function hit(string $key, int $maxRequests, int $windowSeconds): RateLimitResult
    {
        $window = (int) floor(time() / $windowSeconds);
        $cacheKey = "ratelimit:{$key}:{$window}";

        $count = apcu_inc($cacheKey, 1, $success, $windowSeconds);
        if ($count === false) {
            // First hit in this window — apcu_inc fails against a missing key.
            apcu_add($cacheKey, 1, $windowSeconds);
            $count = 1;
        }

        $windowResetAt = ($window + 1) * $windowSeconds;

        return new RateLimitResult(
            allowed: $count <= $maxRequests,
            remaining: max(0, $maxRequests - $count),
            retryAfterSeconds: max(0, $windowResetAt - time()),
        );
    }
}
