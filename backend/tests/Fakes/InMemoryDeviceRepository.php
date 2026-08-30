<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Device;
use App\Repository\Contract\DeviceRepositoryInterface;

final class InMemoryDeviceRepository implements DeviceRepositoryInterface
{
    /** @var array<string, Device> */
    private array $devices = [];

    public function findById(string $id): ?Device
    {
        return $this->devices[$id] ?? null;
    }

    public function register(Device $device): Device
    {
        $this->devices[$device->id] = $device;

        return $device;
    }

    public function delete(string $id): void
    {
        unset($this->devices[$id]);
    }

    public function findPushTokensForUsers(array $userIds): array
    {
        return array_values(array_map(
            static fn (Device $d) => $d->pushToken,
            array_filter($this->devices, static fn (Device $d) => in_array($d->userId, $userIds, true)),
        ));
    }
}
