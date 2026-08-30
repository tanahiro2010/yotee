<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

/**
 * Fetches a provider's JWKS (JSON Web Key Set) and caches it on local disk
 * so verifying a login token doesn't cost a network round trip on every
 * request — Google/Apple's own keys rotate on the order of days, not
 * per-request, so a short TTL cache is both safe and a meaningful latency
 * win (PRD §65 Performance).
 */
final class JwksCache
{
    public function __construct(
        private readonly string $cacheDirectory,
        private readonly int $ttlSeconds = 3600,
        private readonly int $httpTimeoutSeconds = 5,
    ) {
    }

    /** @return array<string, mixed> Decoded JWKS `keys` document. */
    public function get(string $jwksUrl): array
    {
        $cacheFile = $this->cacheDirectory . '/' . hash('sha256', $jwksUrl) . '.json';

        $cached = $this->readFresh($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $fresh = $this->fetch($jwksUrl);
        $this->write($cacheFile, $fresh);

        return $fresh;
    }

    private function readFresh(string $cacheFile): ?array
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        if (time() - (int) filemtime($cacheFile) > $this->ttlSeconds) {
            return null;
        }

        $raw = file_get_contents($cacheFile);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function fetch(string $jwksUrl): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->httpTimeoutSeconds,
                'header' => "Accept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($jwksUrl, false, $context);
        if ($raw === false) {
            throw new \RuntimeException("Unable to fetch JWKS from {$jwksUrl}");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['keys'])) {
            throw new \RuntimeException("Malformed JWKS response from {$jwksUrl}");
        }

        return $decoded;
    }

    private function write(string $cacheFile, array $data): void
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0700, true);
        }

        // Write-then-rename keeps a concurrent reader from ever seeing a
        // half-written file.
        $tmp = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_THROW_ON_ERROR));
        rename($tmp, $cacheFile);
    }
}
