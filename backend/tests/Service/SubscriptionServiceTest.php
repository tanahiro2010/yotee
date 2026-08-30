<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Domain\CategoryVisibility;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\NotFoundException;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Service\CategoryService;
use App\Service\SubscriptionService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryItemRepository;
use Tests\Fakes\InMemorySubscriptionRepository;

/** PRD §74 Backend Unit Test focus: Subscribe. */
final class SubscriptionServiceTest extends TestCase
{
    private InMemoryCategoryRepository $categories;
    private CategoryService $categoryService;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        $this->categories = new InMemoryCategoryRepository();
        $uuids = new UuidGenerator();
        $this->categoryService = new CategoryService($this->categories, new InMemoryItemRepository(), $uuids);
        $this->subscriptionService = new SubscriptionService(new InMemorySubscriptionRepository(), $this->categories, $uuids);
    }

    public function testSubscribingToAPublicCategorySucceeds(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $subscription = $this->subscriptionService->subscribe($category->id, 'subscriber-1');

        self::assertSame($category->id, $subscription->categoryId);
        self::assertSame('subscriber-1', $subscription->userId);
    }

    public function testSubscribingToAnUnlistedCategorySucceeds(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Unlisted);

        $subscription = $this->subscriptionService->subscribe($category->id, 'subscriber-1');

        self::assertSame($category->id, $subscription->categoryId);
    }

    public function testSubscribingToSomeoneElsesPrivateCategoryIsNotFound(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Private);

        $this->expectException(NotFoundException::class);
        $this->subscriptionService->subscribe($category->id, 'subscriber-1');
    }

    public function testSubscribingTwiceConflicts(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);
        $this->subscriptionService->subscribe($category->id, 'subscriber-1');

        $this->expectException(ConflictException::class);
        $this->subscriptionService->subscribe($category->id, 'subscriber-1');
    }

    public function testUnsubscribingSomethingNeverSubscribedToIsNotFound(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $this->expectException(NotFoundException::class);
        $this->subscriptionService->unsubscribe($category->id, 'subscriber-1');
    }

    public function testUnsubscribeThenListForUserIsEmpty(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);
        $this->subscriptionService->subscribe($category->id, 'subscriber-1');

        $this->subscriptionService->unsubscribe($category->id, 'subscriber-1');

        self::assertSame([], $this->subscriptionService->listForUser('subscriber-1'));
    }

    private function createCategory(string $ownerId, CategoryVisibility $visibility): \App\Domain\Category
    {
        return $this->categoryService->create($ownerId, [
            'name' => 'Test List',
            'description' => null,
            'visibility' => $visibility,
            'timezone' => 'Asia/Tokyo',
            'recommendedReminder' => null,
        ]);
    }
}
