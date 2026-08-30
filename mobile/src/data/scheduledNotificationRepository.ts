import type { SQLiteDatabase } from 'expo-sqlite';

/** Tracks which deterministic Local Notification ids are currently registered with the OS for a given Item — the source of truth for what to cancel on edit (PRD §25). */
export interface ScheduledNotificationRow {
  id: string;
  item_id: string;
  occurrence_at: string;
  reminder_at: string;
}

export async function listScheduledNotificationIdsForItem(db: SQLiteDatabase, itemId: string): Promise<string[]> {
  const rows = await db.getAllAsync<{ id: string }>('SELECT id FROM scheduled_notifications WHERE item_id = ?', itemId);
  return rows.map((row) => row.id);
}

/** Every id currently registered with the OS, across every Item — the base for the global 48-notification cap (PRD §24). */
export async function listAllScheduledNotificationIds(db: SQLiteDatabase): Promise<string[]> {
  const rows = await db.getAllAsync<{ id: string }>('SELECT id FROM scheduled_notifications');
  return rows.map((row) => row.id);
}

export async function replaceScheduledNotificationsForItem(
  db: SQLiteDatabase,
  itemId: string,
  entries: { id: string; occurrenceAt: Date; reminderAt: Date }[],
): Promise<void> {
  await db.withTransactionAsync(async () => {
    await db.runAsync('DELETE FROM scheduled_notifications WHERE item_id = ?', itemId);
    for (const entry of entries) {
      await db.runAsync(
        'INSERT INTO scheduled_notifications (id, item_id, occurrence_at, reminder_at) VALUES (?, ?, ?, ?)',
        entry.id,
        itemId,
        entry.occurrenceAt.toISOString(),
        entry.reminderAt.toISOString(),
      );
    }
  });
}

export async function deleteScheduledNotificationsForItem(db: SQLiteDatabase, itemId: string): Promise<void> {
  await db.runAsync('DELETE FROM scheduled_notifications WHERE item_id = ?', itemId);
}

/**
 * Replaces the entire table in one go — used by the global refresh
 * (notificationOrchestrator.refreshAllNotifications) after it re-derives
 * every Item's notifications from scratch, so a disabled/deleted Item's
 * stale rows can't linger just because that Item wasn't touched directly.
 */
export async function replaceAllScheduledNotifications(
  db: SQLiteDatabase,
  entries: { id: string; itemId: string; occurrenceAt: Date; reminderAt: Date }[],
): Promise<void> {
  await db.withTransactionAsync(async () => {
    await db.runAsync('DELETE FROM scheduled_notifications');
    for (const entry of entries) {
      await db.runAsync(
        'INSERT INTO scheduled_notifications (id, item_id, occurrence_at, reminder_at) VALUES (?, ?, ?, ?)',
        entry.id,
        entry.itemId,
        entry.occurrenceAt.toISOString(),
        entry.reminderAt.toISOString(),
      );
    }
  });
}
