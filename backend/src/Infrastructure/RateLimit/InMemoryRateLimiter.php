<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

/**
 * Per-process fallback for environments without APCu (local dev, CLI,
 * tests). Not shared across PHP-FPM workers, so it under-counts in
 * production — ApcuRateLimiter is what actually runs there.
 */
final class InMemoryRateLimiter implements RateLimiterInterface
{
    /** @var array<string, int> */
    private array $counts = [];

    public function hit(string $key, int $maxRequests, int $windowSeconds): RateLimitResult
    {
        $window = (int) floor(time() / $windowSeconds);
        $cacheKey = "{$key}:{$window}";

        $count = ($this->counts[$cacheKey] ?? 0) + 1;
        $this->counts[$cacheKey] = $count;

        $windowResetAt = ($window + 1) * $windowSeconds;

        return new RateLimitResult(
            allowed: $count <= $maxRequests,
            remaining: max(0, $maxRequests - $count),
            retryAfterSeconds: max(0, $windowResetAt - time()),
        );
    }
}
