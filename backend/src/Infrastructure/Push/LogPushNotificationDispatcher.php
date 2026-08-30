<?php

declare(strict_types=1);

namespace App\Infrastructure\Push;

use Psr\Log\LoggerInterface;

/**
 * Placeholder dispatcher: logs the "would have pushed" event instead of
 * calling APNs/FCM. Real provider wiring (certificates, FCM server key,
 * retry/backoff) is a separate Phase 2 task, not something the PRD pins
 * down at the protocol level — swap this out behind
 * PushNotificationDispatcherInterface without touching any Service code.
 */
final class LogPushNotificationDispatcher implements PushNotificationDispatcherInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function dispatchCategoryUpdated(string $categoryId, int $version, array $deviceTokens): void
    {
        if ($deviceTokens === []) {
            return;
        }

        $this->logger->info('push.category_updated', [
            'categoryId' => $categoryId,
            'version' => $version,
            'deviceCount' => count($deviceTokens),
        ]);
    }
}
