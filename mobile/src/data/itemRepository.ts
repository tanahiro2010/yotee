import type { SQLiteDatabase } from 'expo-sqlite';
import * as Crypto from 'expo-crypto';

import type { ScheduleRule } from '../domain/scheduleEngine/types';
import type { ReminderRule } from '../domain/notificationScheduler/types';
import { itemFromRow, type Item, type ItemRow } from './models';

export interface CreateItemInput {
  categoryId: string;
  name: string;
  description?: string | null;
  scheduleRule: ScheduleRule;
  location?: string | null;
  url?: string | null;
  sortOrder?: number;
  reminderRule?: ReminderRule;
}

export async function listItemsForCategory(db: SQLiteDatabase, categoryId: string): Promise<Item[]> {
  const rows = await db.getAllAsync<ItemRow>(
    'SELECT * FROM items WHERE category_id = ? AND deleted_at IS NULL ORDER BY sort_order ASC, created_at ASC',
    categoryId,
  );
  return rows.map(itemFromRow);
}

/** Every non-deleted Item across every List — what the Home timeline (PRD §13) expands Occurrences from. */
export async function listAllItems(db: SQLiteDatabase): Promise<Item[]> {
  const rows = await db.getAllAsync<ItemRow>('SELECT * FROM items WHERE deleted_at IS NULL ORDER BY sort_order ASC, created_at ASC');
  return rows.map(itemFromRow);
}

export async function getItem(db: SQLiteDatabase, id: string): Promise<Item | null> {
  const row = await db.getFirstAsync<ItemRow>('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', id);
  return row ? itemFromRow(row) : null;
}

export async function createItem(db: SQLiteDatabase, input: CreateItemInput): Promise<Item> {
  const id = Crypto.randomUUID();
  const now = new Date().toISOString();
  const reminderPreset = input.reminderRule?.preset ?? 'night_before';
  const reminderCustomMinutesBefore = input.reminderRule?.customMinutesBefore ?? null;

  await db.runAsync(
    `INSERT INTO items
      (id, category_id, name, description, schedule_type, schedule_rule, location, url, sort_order, reminder_preset, reminder_custom_minutes_before, notifications_enabled, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)`,
    id,
    input.categoryId,
    input.name,
    input.description ?? null,
    input.scheduleRule.scheduleType,
    JSON.stringify(input.scheduleRule),
    input.location ?? null,
    input.url ?? null,
    input.sortOrder ?? 0,
    reminderPreset,
    reminderCustomMinutesBefore,
    now,
    now,
  );

  return {
    id,
    categoryId: input.categoryId,
    name: input.name,
    description: input.description ?? null,
    scheduleRule: input.scheduleRule,
    location: input.location ?? null,
    url: input.url ?? null,
    sortOrder: input.sortOrder ?? 0,
    reminderPreset,
    reminderCustomMinutesBefore,
    notificationsEnabled: true,
    createdAt: now,
    updatedAt: now,
  };
}

/** Toggles per-Item notification ON/OFF (PRD §12) — purely device-local state. */
export async function setItemNotificationsEnabled(db: SQLiteDatabase, id: string, enabled: boolean): Promise<void> {
  await db.runAsync('UPDATE items SET notifications_enabled = ?, updated_at = ? WHERE id = ?', enabled ? 1 : 0, new Date().toISOString(), id);
}

export async function deleteItem(db: SQLiteDatabase, id: string): Promise<void> {
  await db.runAsync('UPDATE items SET deleted_at = ? WHERE id = ?', new Date().toISOString(), id);
}
