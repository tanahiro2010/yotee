import type { Occurrence } from '../scheduleEngine/types';
import { computeReminderAt, deterministicNotificationId } from './reminderTiming';
import type { ReminderPlanEntry, ReminderRule } from './types';

/**
 * Don't register a year of notifications at once — compute ~60 days ahead
 * and backfill more on app launch / sync / background task (PRD §24).
 * These are starting points to tune after on-device testing, per the
 * roadmap's own note; nothing about the math below depends on the exact values.
 */
export const DEFAULT_HORIZON_DAYS = 60;
export const MAX_OS_REGISTERED_NOTIFICATIONS = 48;

export function buildReminderPlanForItem(itemId: string, occurrences: Occurrence[], rule: ReminderRule, timeZone: string): ReminderPlanEntry[] {
  return occurrences.map((occurrence) => ({
    id: deterministicNotificationId(itemId, occurrence, rule),
    itemId,
    occurrenceAt: occurrence.occurrenceAt,
    reminderAt: computeReminderAt(occurrence, rule, timeZone),
  }));
}

/**
 * Across every Item's reminders combined, keep only the soonest
 * `maxCount` that haven't already passed — this is the "cap at 48
 * OS-registered notifications at a time" half of the Horizon (§24); the
 * "~60 days ahead" half is just how far `expandOccurrences`'s range was
 * asked to look in the first place.
 */
export function selectWithinHorizon(entries: ReminderPlanEntry[], now: Date, maxCount: number = MAX_OS_REGISTERED_NOTIFICATIONS): ReminderPlanEntry[] {
  return entries
    .filter((entry) => entry.reminderAt >= now)
    .sort((a, b) => a.reminderAt.getTime() - b.reminderAt.getTime())
    .slice(0, maxCount);
}
