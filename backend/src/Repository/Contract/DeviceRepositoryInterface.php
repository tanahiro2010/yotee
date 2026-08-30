<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Domain\Device;

interface DeviceRepositoryInterface
{
    public function findById(string $id): ?Device;

    /** Upsert by (userId, pushToken) — re-registering the same token just refreshes `lastSeenAt`. */
    public function register(Device $device): Device;

    public function delete(string $id): void;

    /** @param string[] $userIds @return string[] push tokens for those users' registered devices. */
    public function findPushTokensForUsers(array $userIds): array;
}
