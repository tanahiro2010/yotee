namespace Yotee;

/**
 * ISO-8601 weekday numbering used by Weekly / Monthly Nth Weekday rules
 * (PRD §45 example: `weekdays: [2, 5]` = 火・金, so Sunday = 0).
 */
enum Weekday {
  Sunday: 0,
  Monday: 1,
  Tuesday: 2,
  Wednesday: 3,
  Thursday: 4,
  Friday: 5,
  Saturday: 6,
}

enum ScheduleType {
  once: "once",
  weekly: "weekly",
  monthlyDay: "monthly_day",
  monthlyNthWeekday: "monthly_nth_weekday",
  yearly: "yearly",
}

/**
 * Discriminated base model stored as MySQL JSON on `items.schedule_rule`
 * (PRD §44, §45). Concrete shape is one of the five `*ScheduleRule` models
 * below, selected by `scheduleType`.
 *
 * Recurring variants (everything except `once`) deliberately have **no timezone
 * field of their own** — they are local wall-clock time interpreted against the
 * owning Category's `timezone` (PRD §9). This is what keeps "every Monday 20:00"
 * correct across DST transitions instead of drifting by an hour twice a year.
 */
@discriminator("scheduleType")
model ScheduleRule {
  scheduleType: ScheduleType;
}

/** A single non-repeating occurrence (PRD §8 Once, §45 example). */
model OnceScheduleRule extends ScheduleRule {
  scheduleType: ScheduleType.once;

  /**
   * Absolute instant with explicit UTC offset, e.g. `2026-09-30T18:00:00+09:00`.
   * Unlike the recurring variants this is a fixed point in time, so it carries
   * its own offset rather than deferring to the Category timezone.
   */
  at: utcDateTime;
}

/** Repeats every week on one or more weekdays (PRD §8 Weekly). */
model WeeklyScheduleRule extends ScheduleRule {
  scheduleType: ScheduleType.weekly;

  @minItems(1)
  weekdays: Weekday[];

  /** Local time of day, e.g. `08:00`. */
  time: plainTime;
}

/** Repeats every month on a fixed day-of-month (PRD §8 Monthly Day, e.g. 毎月15日). */
model MonthlyDayScheduleRule extends ScheduleRule {
  scheduleType: ScheduleType.monthlyDay;

  @minValue(1)
  @maxValue(31)
  day: int32;

  time: plainTime;
}

/**
 * Repeats every month on the Nth occurrence of a weekday
 * (PRD §8 Monthly Nth Weekday, §45 example: 毎月第2木曜日 → `nth: 2, weekday: 4`).
 */
model MonthlyNthWeekdayScheduleRule extends ScheduleRule {
  scheduleType: ScheduleType.monthlyNthWeekday;

  @minValue(1)
  @maxValue(5)
  nth: int32;

  weekday: Weekday;
  time: plainTime;
}

/** Repeats every year on a fixed month/day (PRD §8 Yearly, e.g. 毎年8月18日). */
model YearlyScheduleRule extends ScheduleRule {
  scheduleType: ScheduleType.yearly;

  @minValue(1)
  @maxValue(12)
  month: int32;

  @minValue(1)
  @maxValue(31)
  day: int32;

  time: plainTime;
}
