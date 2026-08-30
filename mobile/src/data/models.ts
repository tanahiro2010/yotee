import type { CategoryVisibility } from '../domain/category';
import type { ScheduleRule } from '../domain/scheduleEngine/types';
import type { ReminderPreset } from '../domain/notificationScheduler/types';

/** Raw SQLite row shape — snake_case, JSON-as-TEXT, booleans-as-0/1. Never leaves src/data/. */
export interface CategoryRow {
  id: string;
  name: string;
  description: string | null;
  visibility: CategoryVisibility;
  timezone: string;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface ItemRow {
  id: string;
  category_id: string;
  name: string;
  description: string | null;
  schedule_type: string;
  schedule_rule: string; // JSON
  location: string | null;
  url: string | null;
  sort_order: number;
  reminder_preset: ReminderPreset;
  reminder_custom_minutes_before: number | null;
  notifications_enabled: 0 | 1;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

/** App-facing shape — camelCase, parsed JSON, real booleans. What everything outside src/data/ actually works with. */
export interface Category {
  id: string;
  name: string;
  description: string | null;
  visibility: CategoryVisibility;
  timezone: string;
  createdAt: string;
  updatedAt: string;
}

export interface Item {
  id: string;
  categoryId: string;
  name: string;
  description: string | null;
  scheduleRule: ScheduleRule;
  location: string | null;
  url: string | null;
  sortOrder: number;
  reminderPreset: ReminderPreset;
  reminderCustomMinutesBefore: number | null;
  notificationsEnabled: boolean;
  createdAt: string;
  updatedAt: string;
}

export function categoryFromRow(row: CategoryRow): Category {
  return {
    id: row.id,
    name: row.name,
    description: row.description,
    visibility: row.visibility,
    timezone: row.timezone,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  };
}

export function itemFromRow(row: ItemRow): Item {
  return {
    id: row.id,
    categoryId: row.category_id,
    name: row.name,
    description: row.description,
    scheduleRule: JSON.parse(row.schedule_rule) as ScheduleRule,
    location: row.location,
    url: row.url,
    sortOrder: row.sort_order,
    reminderPreset: row.reminder_preset,
    reminderCustomMinutesBefore: row.reminder_custom_minutes_before,
    notificationsEnabled: row.notifications_enabled === 1,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  };
}
