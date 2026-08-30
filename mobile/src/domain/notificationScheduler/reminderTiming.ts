import { fromSerialDay, toSerialDay } from '../scheduleEngine/calendar';
import { zonedTimeToUtc } from '../scheduleEngine/timezone';
import type { Occurrence } from '../scheduleEngine/types';
import type { ReminderRule } from './types';

const NIGHT_BEFORE_HOUR = 20;
const SAME_DAY_MORNING_HOUR = 8;

/**
 * When an Item's Occurrence should actually notify the user (PRD §10, §11).
 * "前日の夜"/"当日の朝" are calendar-relative in the Category's own
 * timezone — not a fixed elapsed duration — so DST doesn't shift them by an
 * hour the way a naive `occurrenceAt - 24h` would.
 */
export function computeReminderAt(occurrence: Occurrence, rule: ReminderRule, timeZone: string): Date {
  const [year, month, day] = occurrence.localDate.split('-').map(Number);

  switch (rule.preset) {
    case 'night_before': {
      const previousDay = fromSerialDay(toSerialDay({ year, month, day }) - 1);
      return zonedTimeToUtc({ ...previousDay, hour: NIGHT_BEFORE_HOUR, minute: 0 }, timeZone);
    }
    case 'same_day_morning':
      return zonedTimeToUtc({ year, month, day, hour: SAME_DAY_MORNING_HOUR, minute: 0 }, timeZone);
    case 'custom': {
      if (rule.customMinutesBefore == null) {
        throw new Error('customMinutesBefore is required when preset is "custom"');
      }
      return new Date(occurrence.occurrenceAt.getTime() - rule.customMinutesBefore * 60_000);
    }
  }
}

/**
 * Stable across re-runs for the same (Item, Occurrence, reminder timing) —
 * this is what lets the client cancel exactly the stale Local Notification
 * on an Item edit and register its replacement, instead of clearing and
 * re-registering everything (PRD §25).
 */
export function deterministicNotificationId(itemId: string, occurrence: Occurrence, rule: ReminderRule): string {
  const reminderKey = rule.preset === 'custom' ? `custom-${rule.customMinutesBefore}` : rule.preset;

  return `${itemId}:${occurrence.occurrenceAt.toISOString()}:${reminderKey}`;
}
