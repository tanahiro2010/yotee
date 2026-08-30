<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Domain\CategoryVisibility;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\NotFoundException;
use App\Domain\ScheduleType;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Service\CategoryService;
use App\Service\ItemService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryDeviceRepository;
use Tests\Fakes\InMemoryItemRepository;
use Tests\Fakes\InMemorySubscriptionRepository;
use Tests\Fakes\SpyPushNotificationDispatcher;

/** PRD §74 Backend Unit Test focus: Item Permission, Version Increment. */
final class ItemServiceTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private InMemorySubscriptionRepository $subscriptions;
    private InMemoryDeviceRepository $devices;
    private SpyPushNotificationDispatcher $pushDispatcher;
    private ItemService $itemService;
    private CategoryService $categoryService;

    protected function setUp(): void
    {
        $this->categories = new InMemoryCategoryRepository();
        $this->subscriptions = new InMemorySubscriptionRepository();
        $this->devices = new InMemoryDeviceRepository();
        $this->pushDispatcher = new SpyPushNotificationDispatcher();

        $uuids = new UuidGenerator();
        $this->categoryService = new CategoryService($this->categories, new InMemoryItemRepository(), $uuids);
        $this->itemService = new ItemService(
            new InMemoryItemRepository(),
            $this->categories,
            $this->subscriptions,
            $this->devices,
            $this->pushDispatcher,
            $uuids,
        );
    }

    public function testCreatingAnItemBumpsCategoryVersionAndDispatchesAPush(): void
    {
        $category = $this->createOwnedCategory('owner-1');
        self::assertSame(1, $category->version);

        $this->itemService->create($category->id, 'owner-1', $this->validItem());

        $reloaded = $this->categories->findById($category->id);
        self::assertSame(2, $reloaded->version);
        self::assertCount(1, $this->pushDispatcher->dispatched);
        self::assertSame(2, $this->pushDispatcher->dispatched[0]['version']);
    }

    public function testCreatingAnItemUnderSomeoneElsesCategoryIsForbidden(): void
    {
        $category = $this->createOwnedCategory('owner-1');

        $this->expectException(ForbiddenException::class);
        $this->itemService->create($category->id, 'attacker', $this->validItem());
    }

    public function testUpdatingAnItemBumpsCategoryVersionAgain(): void
    {
        $category = $this->createOwnedCategory('owner-1');
        $item = $this->itemService->create($category->id, 'owner-1', $this->validItem());

        $this->itemService->update($item->id, 'owner-1', ['name' => '新しい名前']);

        $reloaded = $this->categories->findById($category->id);
        self::assertSame(3, $reloaded->version);
    }

    public function testUpdatingSomeoneElsesItemIsForbidden(): void
    {
        $category = $this->createOwnedCategory('owner-1');
        $item = $this->itemService->create($category->id, 'owner-1', $this->validItem());

        $this->expectException(ForbiddenException::class);
        $this->itemService->update($item->id, 'attacker', ['name' => 'Hijacked']);
    }

    public function testDeletingAnItemSoftDeletesItAndBumpsVersion(): void
    {
        $category = $this->createOwnedCategory('owner-1');
        $item = $this->itemService->create($category->id, 'owner-1', $this->validItem());

        $this->itemService->delete($item->id, 'owner-1');

        $reloaded = $this->categories->findById($category->id);
        self::assertSame(3, $reloaded->version);

        $this->expectException(NotFoundException::class);
        $this->itemService->update($item->id, 'owner-1', ['name' => 'should 404, item is gone']);
    }

    private function createOwnedCategory(string $ownerId): \App\Domain\Category
    {
        return $this->categoryService->create($ownerId, [
            'name' => 'ゴミの日',
            'description' => null,
            'visibility' => CategoryVisibility::Public,
            'timezone' => 'Asia/Tokyo',
            'recommendedReminder' => null,
        ]);
    }

    private function validItem(): array
    {
        return [
            'name' => '燃えるゴミ',
            'description' => null,
            'scheduleType' => ScheduleType::Weekly,
            'scheduleRule' => ['weekdays' => [2, 5], 'time' => '08:00'],
            'location' => null,
            'url' => null,
            'sortOrder' => 0,
        ];
    }
}
