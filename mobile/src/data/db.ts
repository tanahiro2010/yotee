import type { SQLiteDatabase } from 'expo-sqlite';

export const DATABASE_NAME = 'yotee.db';

/**
 * Local SQLite schema (CLAUDE.md §48 "Local SQLite" — optimized for the
 * client, deliberately not a 1:1 mirror of the eventual server schema).
 * Phase 1 has no backend at all (CLAUDE.md phased plan), so this only
 * covers what's needed to prove "list → item → Local Notification" works
 * entirely on-device: `categories` and `items`, plus `scheduled_notifications`
 * to track exactly which Local Notification ids are currently registered
 * with the OS (needed to cancel-then-replace on edit, PRD §25).
 *
 * Migrations are tracked via SQLite's own `PRAGMA user_version`, per
 * expo-sqlite's documented pattern — bump DATABASE_VERSION and add an
 * `if (currentVersion < N)` branch rather than editing an already-shipped one.
 */
const DATABASE_VERSION = 1;

export async function migrateDbIfNeeded(db: SQLiteDatabase): Promise<void> {
  const result = await db.getFirstAsync<{ user_version: number }>('PRAGMA user_version');
  let currentVersion = result?.user_version ?? 0;

  if (currentVersion >= DATABASE_VERSION) {
    return;
  }

  if (currentVersion === 0) {
    await db.execAsync(`
      PRAGMA journal_mode = WAL;

      CREATE TABLE IF NOT EXISTS categories (
        id TEXT PRIMARY KEY NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        visibility TEXT NOT NULL DEFAULT 'private',
        timezone TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        deleted_at TEXT
      );

      CREATE TABLE IF NOT EXISTS items (
        id TEXT PRIMARY KEY NOT NULL,
        category_id TEXT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        description TEXT,
        schedule_type TEXT NOT NULL,
        schedule_rule TEXT NOT NULL,
        location TEXT,
        url TEXT,
        sort_order INTEGER NOT NULL DEFAULT 0,
        reminder_preset TEXT NOT NULL DEFAULT 'night_before',
        reminder_custom_minutes_before INTEGER,
        notifications_enabled INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        deleted_at TEXT
      );

      CREATE INDEX IF NOT EXISTS idx_items_category_id ON items(category_id);

      CREATE TABLE IF NOT EXISTS scheduled_notifications (
        id TEXT PRIMARY KEY NOT NULL,
        item_id TEXT NOT NULL REFERENCES items(id) ON DELETE CASCADE,
        occurrence_at TEXT NOT NULL,
        reminder_at TEXT NOT NULL
      );

      CREATE INDEX IF NOT EXISTS idx_scheduled_notifications_item_id ON scheduled_notifications(item_id);
    `);
    currentVersion = 1;
  }

  await db.execAsync(`PRAGMA user_version = ${DATABASE_VERSION}`);
}
