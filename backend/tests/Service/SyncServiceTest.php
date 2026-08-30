<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Domain\Category;
use App\Domain\CategoryVisibility;
use App\Domain\Subscription;
use App\Service\SyncService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryItemRepository;
use Tests\Fakes\InMemorySubscriptionRepository;

/** PRD §74 Backend Unit Test focus: Sync. */
final class SyncServiceTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private InMemorySubscriptionRepository $subscriptions;
    private SyncService $syncService;

    protected function setUp(): void
    {
        $this->categories = new InMemoryCategoryRepository();
        $this->subscriptions = new InMemorySubscriptionRepository();
        $this->syncService = new SyncService($this->categories, new InMemoryItemRepository(), $this->subscriptions);
    }

    public function testSyncOnlyReturnsCategoriesOwnedOrSubscribedByTheCaller(): void
    {
        $t0 = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $owned = $this->makeCategory('cat-owned', 'user-1', $t0);
        $subscribedTo = $this->makeCategory('cat-subscribed', 'user-2', $t0);
        $unrelated = $this->makeCategory('cat-unrelated', 'user-2', $t0);

        $this->categories->create($owned);
        $this->categories->create($subscribedTo);
        $this->categories->create($unrelated);
        $this->subscriptions->create(new Subscription('sub-1', 'user-1', 'cat-subscribed', $t0));

        $result = $this->syncService->sync('user-1', null);

        $ids = array_map(static fn (Category $c) => $c->id, $result['categories']);
        sort($ids);
        self::assertSame(['cat-owned', 'cat-subscribed'], $ids);
    }

    public function testSyncWithACursorOnlyReturnsWhatChangedAfterIt(): void
    {
        // $t0 anchors to the real clock (not a fixed historical date) because
        // SyncService's own "no more data" cursor fallback is `new
        // DateTimeImmutable()` at call time — $t1 must land after that.
        $t0 = new \DateTimeImmutable();
        $t1 = $t0->modify('+1 hour');

        $categoryA = $this->makeCategory('cat-a', 'user-1', $t0);
        $categoryB = $this->makeCategory('cat-b', 'user-1', $t0);
        $this->categories->create($categoryA);
        $this->categories->create($categoryB);

        $first = $this->syncService->sync('user-1', null);
        self::assertCount(2, $first['categories']);

        // Only A changes after the first sync's cursor.
        $this->categories->save($this->makeCategory('cat-a', 'user-1', $t1, name: 'Renamed'));

        $second = $this->syncService->sync('user-1', $first['nextCursor']);

        self::assertCount(1, $second['categories']);
        self::assertSame('cat-a', $second['categories'][0]->id);
        self::assertSame('Renamed', $second['categories'][0]->name);
    }

    public function testSyncSurfacesSoftDeletedCategoriesSoClientsCanReconcile(): void
    {
        // $t0 anchors to the real clock (not a fixed historical date) because
        // SyncService's own "no more data" cursor fallback is `new
        // DateTimeImmutable()` at call time — $t1 must land after that.
        $t0 = new \DateTimeImmutable();
        $t1 = $t0->modify('+1 hour');

        $this->categories->create($this->makeCategory('cat-a', 'user-1', $t0));
        $first = $this->syncService->sync('user-1', null);

        $this->categories->save($this->makeCategory('cat-a', 'user-1', $t1, deletedAt: $t1));
        $second = $this->syncService->sync('user-1', $first['nextCursor']);

        self::assertCount(1, $second['categories']);
        self::assertNotNull($second['categories'][0]->deletedAt);
    }

    private function makeCategory(
        string $id,
        string $ownerId,
        \DateTimeImmutable $updatedAt,
        string $name = 'Test List',
        ?\DateTimeImmutable $deletedAt = null,
    ): Category {
        return new Category(
            id: $id,
            ownerId: $ownerId,
            name: $name,
            description: null,
            visibility: CategoryVisibility::Public,
            timezone: 'Asia/Tokyo',
            version: 1,
            recommendedReminder: null,
            createdAt: $updatedAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }
}
