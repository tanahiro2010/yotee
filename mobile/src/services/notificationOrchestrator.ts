import * as Notifications from 'expo-notifications';
import type { SQLiteDatabase } from 'expo-sqlite';

import { expandOccurrences } from '../domain/scheduleEngine';
import { DEFAULT_HORIZON_DAYS, MAX_OS_REGISTERED_NOTIFICATIONS, buildReminderPlanForItem, selectWithinHorizon } from '../domain/notificationScheduler';
import type { ReminderPlanEntry } from '../domain/notificationScheduler/types';
import { listCategories } from '../data/categoryRepository';
import { listAllItems } from '../data/itemRepository';
import { listAllScheduledNotificationIds, replaceAllScheduledNotifications } from '../data/scheduledNotificationRepository';

/**
 * Recomputes every Local Notification that should exist right now, across
 * every List/Item, and reconciles that against what the OS actually has
 * registered (PRD §23-25). Deliberately cancels-and-reschedules rather than
 * diffing by id: an Item's *name* isn't part of its deterministic
 * notification id, so a rename wouldn't otherwise refresh stale notification
 * content. Call this on app launch, foreground resume, after any List/Item
 * edit, and from the background task (§27) — never assume any one of those
 * triggers alone is enough.
 */
export async function refreshAllNotifications(db: SQLiteDatabase, now: Date = new Date()): Promise<void> {
  const [categories, items, previouslyScheduledIds] = await Promise.all([listCategories(db), listAllItems(db), listAllScheduledNotificationIds(db)]);

  const categoryById = new Map(categories.map((category) => [category.id, category]));

  const horizonEnd = new Date(now.getTime() + DEFAULT_HORIZON_DAYS * 24 * 60 * 60 * 1000);

  const entriesByItemId = new Map<string, ReminderPlanEntry[]>();
  const categoryNameByItemId = new Map<string, string>();

  for (const item of items) {
    if (!item.notificationsEnabled) {
      continue;
    }
    const category = categoryById.get(item.categoryId);
    if (!category) {
      continue;
    }

    const occurrences = expandOccurrences(item.scheduleRule, category.timezone, { from: now, to: horizonEnd });
    const plan = buildReminderPlanForItem(
      item.id,
      occurrences,
      { preset: item.reminderPreset, customMinutesBefore: item.reminderCustomMinutesBefore ?? undefined },
      category.timezone,
    );

    entriesByItemId.set(item.id, plan);
    categoryNameByItemId.set(item.id, category.name);
  }

  const allEntries = Array.from(entriesByItemId.values()).flat();
  const selected = selectWithinHorizon(allEntries, now, MAX_OS_REGISTERED_NOTIFICATIONS);

  // Cancel everything first — simpler and safer than diffing by id, at the
  // cost of some redundant OS calls on each refresh (this runs on app
  // foreground/sync/background-task, not on every render).
  await Promise.all(previouslyScheduledIds.map((id) => Notifications.cancelScheduledNotificationAsync(id).catch(() => undefined)));

  const itemNameById = new Map(items.map((item) => [item.id, item.name]));
  await Promise.all(
    selected.map((entry) =>
      Notifications.scheduleNotificationAsync({
        identifier: entry.id,
        content: {
          title: itemNameById.get(entry.itemId) ?? '',
          body: categoryNameByItemId.get(entry.itemId) ?? '',
        },
        trigger: { type: Notifications.SchedulableTriggerInputTypes.DATE, date: entry.reminderAt },
      }),
    ),
  );

  // Replacing the whole table (rather than per-item) is what keeps a
  // disabled/deleted Item's stale rows from lingering just because it wasn't
  // touched above.
  await replaceAllScheduledNotifications(db, selected);
}
