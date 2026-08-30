<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

interface RateLimiterInterface
{
    /** Records one hit for $key under a fixed window and reports whether it's still within budget. */
    public function hit(string $key, int $maxRequests, int $windowSeconds): RateLimitResult;
}
