<?php

declare(strict_types=1);

namespace App\Infrastructure\Push;

/**
 * Sends the *only* kind of push this backend ever sends: "this Category
 * changed, go re-sync" (CLAUDE.md core principle) — never a reminder. The
 * backend does not retry indefinitely or treat delivery as guaranteed
 * (PRD §27); this is a best-effort nudge, not the sync mechanism itself.
 */
interface PushNotificationDispatcherInterface
{
    /** @param string[] $deviceTokens */
    public function dispatchCategoryUpdated(string $categoryId, int $version, array $deviceTokens): void;
}
