<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Domain\CategoryVisibility;
use App\Domain\Exception\ForbiddenException;
use App\Domain\Exception\NotFoundException;
use App\Infrastructure\Uuid\UuidGenerator;
use App\Service\CategoryService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryItemRepository;

/** PRD §74 Backend Unit Test focus: Category Permission, Visibility. */
final class CategoryServiceTest extends TestCase
{
    private CategoryService $service;
    private InMemoryCategoryRepository $categories;

    protected function setUp(): void
    {
        $this->categories = new InMemoryCategoryRepository();
        $this->service = new CategoryService($this->categories, new InMemoryItemRepository(), new UuidGenerator());
    }

    public function testCreateStartsAtVersionOne(): void
    {
        $category = $this->service->create('owner-1', [
            'name' => 'ゴミの日',
            'description' => null,
            'visibility' => CategoryVisibility::Public,
            'timezone' => 'Asia/Tokyo',
            'recommendedReminder' => null,
        ]);

        self::assertSame(1, $category->version);
        self::assertSame('owner-1', $category->ownerId);
    }

    public function testOwnerCanReadTheirOwnPrivateCategory(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Private);

        $found = $this->service->findReadable($category->id, 'owner-1');

        self::assertSame($category->id, $found->id);
    }

    public function testNonOwnerGetsNotFoundForAPrivateCategory(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Private);

        $this->expectException(NotFoundException::class);
        $this->service->findReadable($category->id, 'someone-else');
    }

    public function testAnonymousCallerGetsNotFoundForAPrivateCategory(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Private);

        $this->expectException(NotFoundException::class);
        $this->service->findReadable($category->id, null);
    }

    public function testAnyoneCanReadAPublicCategory(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $found = $this->service->findReadable($category->id, 'someone-else');

        self::assertSame($category->id, $found->id);
    }

    public function testAnyoneCanReadAnUnlistedCategoryGivenItsId(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Unlisted);

        $found = $this->service->findReadable($category->id, null);

        self::assertSame($category->id, $found->id);
    }

    public function testUpdateByNonOwnerIsForbidden(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $this->expectException(ForbiddenException::class);
        $this->service->update($category->id, 'someone-else', ['name' => 'Hijacked']);
    }

    public function testUpdateByOwnerDoesNotBumpVersion(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $updated = $this->service->update($category->id, 'owner-1', ['name' => 'New Name']);

        self::assertSame('New Name', $updated->name);
        // Metadata edits don't need subscriber devices to reschedule
        // anything, so `version` (which drives that) stays put (PRD §20).
        self::assertSame($category->version, $updated->version);
    }

    public function testDeleteByNonOwnerIsForbidden(): void
    {
        $category = $this->createCategory('owner-1', CategoryVisibility::Public);

        $this->expectException(ForbiddenException::class);
        $this->service->delete($category->id, 'someone-else');
    }

    public function testSearchOnlyReturnsPublicCategories(): void
    {
        $this->createCategory('owner-1', CategoryVisibility::Public, 'ゴミの日 三田市');
        $this->createCategory('owner-1', CategoryVisibility::Private, 'ゴミの日 秘密');

        $result = $this->service->search('ゴミの日', null, null);

        self::assertCount(1, $result['items']);
        self::assertSame('ゴミの日 三田市', $result['items'][0]->name);
    }

    private function createCategory(string $ownerId, CategoryVisibility $visibility, string $name = 'Test List'): \App\Domain\Category
    {
        return $this->service->create($ownerId, [
            'name' => $name,
            'description' => null,
            'visibility' => $visibility,
            'timezone' => 'Asia/Tokyo',
            'recommendedReminder' => null,
        ]);
    }
}
