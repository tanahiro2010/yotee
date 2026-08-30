<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Infrastructure\Push\PushNotificationDispatcherInterface;

final class SpyPushNotificationDispatcher implements PushNotificationDispatcherInterface
{
    /** @var array<array{categoryId: string, version: int, deviceTokens: string[]}> */
    public array $dispatched = [];

    public function dispatchCategoryUpdated(string $categoryId, int $version, array $deviceTokens): void
    {
        $this->dispatched[] = ['categoryId' => $categoryId, 'version' => $version, 'deviceTokens' => $deviceTokens];
    }
}
