/**
 * Pure calendar arithmetic — no timezone/DST concerns at all. Everything
 * here treats {year, month, day} as an abstract calendar date and uses
 * `Date.UTC` purely as a day-counter, never as a real-world instant. This
 * keeps the hardest-to-get-right math (month-end clamping, leap years,
 * nth-weekday-of-month) independently testable from timezone.ts's DST work
 * (PRD §74 test list: weekday境界, 月末, うるう年, 第N曜日, DST).
 */

export type Weekday = 0 | 1 | 2 | 3 | 4 | 5 | 6; // 0 = Sunday ... 6 = Saturday, matching docs/api's Weekday enum

export interface CalendarDate {
  year: number;
  month: number; // 1-12
  day: number;
}

export function isLeapYear(year: number): boolean {
  return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
}

export function daysInMonth(year: number, month: number): number {
  // Date.UTC's month arg is 0-indexed; day 0 of month `month` (1-12, i.e.
  // 1-indexed "next" month in 0-indexed terms) is the last day of the
  // 1-12-indexed `month` itself.
  return new Date(Date.UTC(year, month, 0)).getUTCDate();
}

/** 0 (Sunday) through 6 (Saturday), independent of the host device's local timezone. */
export function weekdayOf(year: number, month: number, day: number): Weekday {
  return new Date(Date.UTC(year, month - 1, day)).getUTCDay() as Weekday;
}

/** Day count since an arbitrary epoch — an opaque counter, only ever compared to another serial from this same function. */
export function toSerialDay(date: CalendarDate): number {
  return Math.floor(Date.UTC(date.year, date.month - 1, date.day) / 86_400_000);
}

export function fromSerialDay(serial: number): CalendarDate {
  const d = new Date(serial * 86_400_000);
  return { year: d.getUTCFullYear(), month: d.getUTCMonth() + 1, day: d.getUTCDate() };
}

/**
 * Adds `monthsToAdd` calendar months to {year, month}, clamping `day` to the
 * resulting month's length (PRD Monthly Day rule, e.g. 31st in a 30-day
 * month) rather than overflowing into the following month.
 */
export function addMonthsClamped(date: CalendarDate, monthsToAdd: number): CalendarDate {
  const totalMonths = date.year * 12 + (date.month - 1) + monthsToAdd;
  const year = Math.floor(totalMonths / 12);
  const month = (totalMonths % 12) + 1;

  return { year, month, day: Math.min(date.day, daysInMonth(year, month)) };
}

/**
 * The date of the Nth occurrence of `weekday` in `month` (PRD Monthly Nth
 * Weekday, e.g. 毎月第2木曜日), or `null` if that month doesn't have one —
 * a "5th Friday" recurrence simply skips months with only four, rather than
 * clamping to the 4th.
 */
export function nthWeekdayOfMonth(year: number, month: number, nth: number, weekday: Weekday): CalendarDate | null {
  const firstWeekday = weekdayOf(year, month, 1);
  const firstOccurrenceDay = 1 + ((weekday - firstWeekday + 7) % 7);
  const day = firstOccurrenceDay + (nth - 1) * 7;

  return day <= daysInMonth(year, month) ? { year, month, day } : null;
}

export function compareCalendarDate(a: CalendarDate, b: CalendarDate): number {
  return toSerialDay(a) - toSerialDay(b);
}
