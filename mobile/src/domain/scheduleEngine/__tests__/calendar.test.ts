import { addMonthsClamped, daysInMonth, isLeapYear, nthWeekdayOfMonth, weekdayOf } from '../calendar';

describe('isLeapYear', () => {
  it('treats years divisible by 4 as leap', () => {
    expect(isLeapYear(2024)).toBe(true);
  });

  it('treats century years not divisible by 400 as non-leap', () => {
    expect(isLeapYear(1900)).toBe(false);
  });

  it('treats century years divisible by 400 as leap', () => {
    expect(isLeapYear(2000)).toBe(true);
  });

  it('treats ordinary non-multiple-of-4 years as non-leap', () => {
    expect(isLeapYear(2025)).toBe(false);
  });
});

describe('daysInMonth', () => {
  it('returns 29 for February in a leap year', () => {
    expect(daysInMonth(2024, 2)).toBe(29);
  });

  it('returns 28 for February in a non-leap year', () => {
    expect(daysInMonth(2025, 2)).toBe(28);
  });

  it('returns 31 for a 31-day month', () => {
    expect(daysInMonth(2026, 1)).toBe(31);
  });

  it('returns 30 for a 30-day month', () => {
    expect(daysInMonth(2026, 4)).toBe(30);
  });
});

describe('weekdayOf', () => {
  it('matches a known reference date (2026-08-30 is a Sunday)', () => {
    expect(weekdayOf(2026, 8, 30)).toBe(0);
  });

  it('matches a known Tuesday', () => {
    // 2026-09-01 is a Tuesday.
    expect(weekdayOf(2026, 9, 1)).toBe(2);
  });
});

describe('addMonthsClamped', () => {
  it('clamps the day when the target month is shorter', () => {
    expect(addMonthsClamped({ year: 2026, month: 1, day: 31 }, 1)).toEqual({ year: 2026, month: 2, day: 28 });
  });

  it('rolls over into the next year', () => {
    expect(addMonthsClamped({ year: 2026, month: 12, day: 15 }, 1)).toEqual({ year: 2027, month: 1, day: 15 });
  });

  it('does not clamp when the day fits in the target month', () => {
    expect(addMonthsClamped({ year: 2026, month: 1, day: 15 }, 1)).toEqual({ year: 2026, month: 2, day: 15 });
  });
});

describe('nthWeekdayOfMonth', () => {
  it('finds the 2nd Thursday of September 2026 (matches PRD §45 example shape)', () => {
    // September 2026: Sep 1 is a Tuesday, so the 1st Thursday is Sep 3, the 2nd is Sep 10.
    expect(nthWeekdayOfMonth(2026, 9, 2, 4)).toEqual({ year: 2026, month: 9, day: 10 });
  });

  it('returns null when a 5th occurrence does not exist that month', () => {
    // February 2026 has 28 days starting on a Sunday — only four Fridays.
    expect(nthWeekdayOfMonth(2026, 2, 5, 5)).toBeNull();
  });

  it('finds a 5th occurrence when the month is long enough', () => {
    // January 2027: Jan 1 is a Friday, so Fridays fall on 1, 8, 15, 22, 29.
    expect(nthWeekdayOfMonth(2027, 1, 5, 5)).toEqual({ year: 2027, month: 1, day: 29 });
  });
});
