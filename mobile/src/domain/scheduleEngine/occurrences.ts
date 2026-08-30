import {
  addMonthsClamped,
  daysInMonth,
  fromSerialDay,
  nthWeekdayOfMonth,
  toSerialDay,
  weekdayOf,
  type CalendarDate,
} from './calendar';
import { utcToLocalDate, zonedTimeToUtc } from './timezone';
import type {
  MonthlyDayScheduleRule,
  MonthlyNthWeekdayScheduleRule,
  Occurrence,
  OccurrenceRange,
  OnceScheduleRule,
  ScheduleRule,
  WeeklyScheduleRule,
  YearlyScheduleRule,
} from './types';

/** Hard ceiling on how many calendar units a single expansion call will walk — a malformed rule must fail fast, not hang. */
const MAX_DAYS_WALKED = 366 * 3;
const MAX_MONTHS_WALKED = 12 * 5;
const MAX_YEARS_WALKED = 20;

function parseTime(time: string): { hour: number; minute: number } {
  const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(time);
  if (!match) {
    throw new Error(`Invalid time "${time}", expected HH:MM 24-hour format`);
  }

  return { hour: Number(match[1]), minute: Number(match[2]) };
}

function formatLocalDate(date: CalendarDate): string {
  return `${date.year.toString().padStart(4, '0')}-${date.month.toString().padStart(2, '0')}-${date.day.toString().padStart(2, '0')}`;
}

function toOccurrenceIfInRange(date: CalendarDate, hour: number, minute: number, timeZone: string, range: OccurrenceRange): Occurrence | null {
  const occurrenceAt = zonedTimeToUtc({ ...date, hour, minute }, timeZone);
  if (occurrenceAt < range.from || occurrenceAt > range.to) {
    return null;
  }

  return { occurrenceAt, localDate: formatLocalDate(date) };
}

export function expandOccurrences(rule: ScheduleRule, timeZone: string, range: OccurrenceRange): Occurrence[] {
  switch (rule.scheduleType) {
    case 'once':
      return expandOnce(rule, range);
    case 'weekly':
      return expandWeekly(rule, timeZone, range);
    case 'monthly_day':
      return expandMonthlyDay(rule, timeZone, range);
    case 'monthly_nth_weekday':
      return expandMonthlyNthWeekday(rule, timeZone, range);
    case 'yearly':
      return expandYearly(rule, timeZone, range);
  }
}

function expandOnce(rule: OnceScheduleRule, range: OccurrenceRange): Occurrence[] {
  const at = new Date(rule.at);
  if (Number.isNaN(at.getTime())) {
    throw new Error(`Invalid Once rule "at" value: ${rule.at}`);
  }
  if (at < range.from || at > range.to) {
    return [];
  }

  return [{ occurrenceAt: at, localDate: at.toISOString().slice(0, 10) }];
}

function expandWeekly(rule: WeeklyScheduleRule, timeZone: string, range: OccurrenceRange): Occurrence[] {
  const { hour, minute } = parseTime(rule.time);
  const weekdays = new Set(rule.weekdays);

  const startSerial = toSerialDay(utcToLocalDate(range.from, timeZone));
  const endSerial = toSerialDay(utcToLocalDate(range.to, timeZone));

  const results: Occurrence[] = [];
  for (let serial = startSerial, steps = 0; serial <= endSerial && steps < MAX_DAYS_WALKED; serial += 1, steps += 1) {
    const date = fromSerialDay(serial);
    if (!weekdays.has(weekdayOf(date.year, date.month, date.day))) {
      continue;
    }

    const occurrence = toOccurrenceIfInRange(date, hour, minute, timeZone, range);
    if (occurrence) {
      results.push(occurrence);
    }
  }

  return results;
}

function expandMonthlyDay(rule: MonthlyDayScheduleRule, timeZone: string, range: OccurrenceRange): Occurrence[] {
  const { hour, minute } = parseTime(rule.time);
  const start = utcToLocalDate(range.from, timeZone);
  const end = utcToLocalDate(range.to, timeZone);

  const results: Occurrence[] = [];
  let cursor: CalendarDate = { year: start.year, month: start.month, day: 1 };
  for (let steps = 0; steps < MAX_MONTHS_WALKED; steps += 1) {
    if (cursor.year > end.year || (cursor.year === end.year && cursor.month > end.month)) {
      break;
    }

    // Clamped to the month's length (documented product decision) rather
    // than skipped — "every month on the 31st" lands on the 28th/29th/30th
    // in shorter months instead of not firing that month at all.
    const day = Math.min(rule.day, daysInMonth(cursor.year, cursor.month));
    const occurrence = toOccurrenceIfInRange({ year: cursor.year, month: cursor.month, day }, hour, minute, timeZone, range);
    if (occurrence) {
      results.push(occurrence);
    }

    cursor = addMonthsClamped(cursor, 1);
  }

  return results;
}

function expandMonthlyNthWeekday(rule: MonthlyNthWeekdayScheduleRule, timeZone: string, range: OccurrenceRange): Occurrence[] {
  const { hour, minute } = parseTime(rule.time);
  const start = utcToLocalDate(range.from, timeZone);
  const end = utcToLocalDate(range.to, timeZone);

  const results: Occurrence[] = [];
  let cursor: CalendarDate = { year: start.year, month: start.month, day: 1 };
  for (let steps = 0; steps < MAX_MONTHS_WALKED; steps += 1) {
    if (cursor.year > end.year || (cursor.year === end.year && cursor.month > end.month)) {
      break;
    }

    const date = nthWeekdayOfMonth(cursor.year, cursor.month, rule.nth, rule.weekday);
    if (date) {
      const occurrence = toOccurrenceIfInRange(date, hour, minute, timeZone, range);
      if (occurrence) {
        results.push(occurrence);
      }
    }

    cursor = addMonthsClamped(cursor, 1);
  }

  return results;
}

function expandYearly(rule: YearlyScheduleRule, timeZone: string, range: OccurrenceRange): Occurrence[] {
  const { hour, minute } = parseTime(rule.time);
  const start = utcToLocalDate(range.from, timeZone);
  const end = utcToLocalDate(range.to, timeZone);

  const results: Occurrence[] = [];
  for (let year = start.year, steps = 0; year <= end.year && steps < MAX_YEARS_WALKED; year += 1, steps += 1) {
    // Clamped the same way as Monthly Day — a Feb 29 yearly rule lands on
    // Feb 28 in non-leap years instead of being skipped.
    const day = Math.min(rule.day, daysInMonth(year, rule.month));
    const occurrence = toOccurrenceIfInRange({ year, month: rule.month, day }, hour, minute, timeZone, range);
    if (occurrence) {
      results.push(occurrence);
    }
  }

  return results;
}
