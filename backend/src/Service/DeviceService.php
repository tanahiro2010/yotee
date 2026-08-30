<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Device;
use App\Domain\DevicePlatform;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\NotFoundException;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Repository\Contract\DeviceRepositoryInterface;

final class DeviceService
{
    public function __construct(
        private readonly DeviceRepositoryInterface $devices,
        private readonly UuidGenerator $uuids,
    ) {
    }

    public function register(string $userId, DevicePlatform $platform, string $pushToken): Device
    {
        $now = new \DateTimeImmutable();

        return $this->devices->register(new Device(
            id: $this->uuids->generate(),
            userId: $userId,
            platform: $platform,
            pushToken: $pushToken,
            lastSeenAt: $now,
            createdAt: $now,
            updatedAt: $now,
        ));
    }

    public function unregister(string $deviceId, string $currentUserId): void
    {
        $device = $this->devices->findById($deviceId);
        if ($device === null) {
            throw new NotFoundException('Device');
        }

        if (!hash_equals($device->userId, $currentUserId)) {
            throw new ForbiddenException();
        }

        $this->devices->delete($deviceId);
    }
}
