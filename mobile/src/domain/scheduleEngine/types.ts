import type { Weekday } from './calendar';

export type { Weekday };

/** Mirrors docs/api's ScheduleRule discriminated union (PRD §8, §45) — keep both in sync. */
export type ScheduleRule =
  | OnceScheduleRule
  | WeeklyScheduleRule
  | MonthlyDayScheduleRule
  | MonthlyNthWeekdayScheduleRule
  | YearlyScheduleRule;

export interface OnceScheduleRule {
  scheduleType: 'once';
  /** ISO-8601 instant with an explicit UTC offset, e.g. `2026-09-30T18:00:00+09:00`. */
  at: string;
}

export interface WeeklyScheduleRule {
  scheduleType: 'weekly';
  weekdays: Weekday[];
  /** Local time of day, `HH:MM`. */
  time: string;
}

export interface MonthlyDayScheduleRule {
  scheduleType: 'monthly_day';
  day: number; // 1-31
  time: string;
}

export interface MonthlyNthWeekdayScheduleRule {
  scheduleType: 'monthly_nth_weekday';
  nth: number; // 1-5
  weekday: Weekday;
  time: string;
}

export interface YearlyScheduleRule {
  scheduleType: 'yearly';
  month: number; // 1-12
  day: number; // 1-31
  time: string;
}

/** A single concrete instance of a ScheduleRule, resolved to a real UTC instant. */
export interface Occurrence {
  /** The real-world instant this occurrence happens at. */
  occurrenceAt: Date;
  /** The occurrence's own local calendar date (`YYYY-MM-DD`) — used to key reminder-timing math and dedupe. */
  localDate: string;
}

export interface OccurrenceRange {
  from: Date;
  to: Date;
}
