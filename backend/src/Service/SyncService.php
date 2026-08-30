<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Category;
use App\Domain\Exception\ValidationException;
use App\Domain\Item;
use App\Domain\Subscription;
use App\Infrastructure\Database\DateTimeCodec;
use App\Repository\Contract\CategoryRepositoryInterface;
use App\Repository\Contract\ItemRepositoryInterface;
use App\Repository\Contract\SubscriptionRepositoryInterface;

/**
 * Delta sync (PRD §49, §50): only Categories/Items/Subscriptions the caller
 * owns or is subscribed to, changed since the opaque cursor. Cursor is a
 * base64 JSON blob of three independent per-collection timestamps rather
 * than one combined value — each collection advances at its own pace and
 * none of them share a table, so there's no natural single "position".
 *
 * Simplification worth knowing about: this compares `updated_at`/`created_at`
 * with strict `>`, at DATETIME(6) (microsecond) precision. Two writes to the
 * same collection landing in the exact same microsecond could in theory
 * leave one of them unseen by a client polling exactly between them — judged
 * an acceptable MVP trade-off over a full (timestamp, id) keyset cursor.
 */
final class SyncService
{
    private const PAGE_LIMIT = 200;

    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ItemRepositoryInterface $items,
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    /** @return array{categories: Category[], items: Item[], subscriptions: Subscription[], nextCursor: string} */
    public function sync(string $userId, ?string $cursor): array
    {
        $now = new \DateTimeImmutable();
        $decoded = self::decodeCursor($cursor);

        $categoryIds = array_values(array_unique(array_merge(
            $this->categories->findIdsOwnedBy($userId),
            $this->subscriptions->findCategoryIdsSubscribedBy($userId),
        )));

        $categories = $this->categories->findUpdatedSince($categoryIds, $decoded['categories'], self::PAGE_LIMIT);
        $items = $this->items->findUpdatedSince($categoryIds, $decoded['items'], self::PAGE_LIMIT);
        $subscriptions = $this->subscriptions->findCreatedSince($userId, $decoded['subscriptions'], self::PAGE_LIMIT);

        $nextCursor = self::encodeCursor([
            'categories' => self::nextPosition($categories, self::PAGE_LIMIT, static fn (Category $c) => $c->updatedAt, $now),
            'items' => self::nextPosition($items, self::PAGE_LIMIT, static fn (Item $i) => $i->updatedAt, $now),
            'subscriptions' => self::nextPosition($subscriptions, self::PAGE_LIMIT, static fn (Subscription $s) => $s->createdAt, $now),
        ]);

        return compact('categories', 'items', 'subscriptions') + ['nextCursor' => $nextCursor];
    }

    /**
     * @template T
     * @param T[] $rows
     * @param callable(T): \DateTimeImmutable $timestampOf
     */
    private static function nextPosition(array $rows, int $limit, callable $timestampOf, \DateTimeImmutable $now): \DateTimeImmutable
    {
        if (count($rows) < $limit) {
            // Exhausted this collection as of $now — advance to $now rather
            // than re-scanning from the same cursor on every future call.
            return $now;
        }

        // A full page came back: there may be more past the last row, so
        // resume exactly from its timestamp rather than jumping to $now.
        return $timestampOf($rows[array_key_last($rows)]);
    }

    /** @return array{categories: ?\DateTimeImmutable, items: ?\DateTimeImmutable, subscriptions: ?\DateTimeImmutable} */
    private static function decodeCursor(?string $cursor): array
    {
        $default = ['categories' => null, 'items' => null, 'subscriptions' => null];
        if ($cursor === null || $cursor === '') {
            return $default;
        }

        $raw = base64_decode($cursor, true);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            throw new ValidationException(['cursor' => 'is not a valid sync cursor']);
        }

        foreach (['categories', 'items', 'subscriptions'] as $key) {
            $value = $decoded[$key] ?? null;
            if ($value === null) {
                continue;
            }
            if (!is_string($value)) {
                throw new ValidationException(['cursor' => 'is not a valid sync cursor']);
            }
            try {
                $default[$key] = DateTimeCodec::fromDb($value);
            } catch (\RuntimeException) {
                throw new ValidationException(['cursor' => 'is not a valid sync cursor']);
            }
        }

        return $default;
    }

    /**
     * Uses the same microsecond-precision codec as the DATETIME(6) columns
     * themselves (DateTimeCodec) rather than DATE_ATOM, which only has
     * whole-second resolution — with that, two rows written in the same
     * second (routine under test, and not impossible in production either)
     * would round-trip through the cursor as indistinguishable from "now",
     * re-including already-synced rows on the very next call.
     *
     * @param array{categories: \DateTimeImmutable, items: \DateTimeImmutable, subscriptions: \DateTimeImmutable} $positions
     */
    private static function encodeCursor(array $positions): string
    {
        $json = json_encode([
            'categories' => DateTimeCodec::toDb($positions['categories']),
            'items' => DateTimeCodec::toDb($positions['items']),
            'subscriptions' => DateTimeCodec::toDb($positions['subscriptions']),
        ], JSON_THROW_ON_ERROR);

        return base64_encode($json);
    }
}
