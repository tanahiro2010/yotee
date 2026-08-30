/** Mirrors docs/api's ReminderPreset — the only three timings the UI ever exposes (CLAUDE.md product principle #3: never show cron syntax). */
export type ReminderPreset = 'night_before' | 'same_day_morning' | 'custom';

export interface ReminderRule {
  preset: ReminderPreset;
  /** Required, and only meaningful, when `preset` is `custom`. */
  customMinutesBefore?: number;
}

export interface ReminderPlanEntry {
  /** Deterministic Local Notification id — see `deterministicNotificationId` (PRD §25). */
  id: string;
  itemId: string;
  occurrenceAt: Date;
  reminderAt: Date;
}
