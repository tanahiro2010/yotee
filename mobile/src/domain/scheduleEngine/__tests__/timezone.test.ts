import { utcToLocalDate, zonedTimeToUtc } from '../timezone';

describe('zonedTimeToUtc', () => {
  it('converts Asia/Tokyo (fixed UTC+9, no DST) correctly', () => {
    const at = zonedTimeToUtc({ year: 2026, month: 9, day: 1, hour: 8, minute: 0 }, 'Asia/Tokyo');
    expect(at.toISOString()).toBe('2026-08-31T23:00:00.000Z');
  });

  it('converts America/New_York in EST (winter, UTC-5)', () => {
    const at = zonedTimeToUtc({ year: 2026, month: 1, day: 15, hour: 20, minute: 0 }, 'America/New_York');
    expect(at.toISOString()).toBe('2026-01-16T01:00:00.000Z');
  });

  it('converts America/New_York in EDT (summer, UTC-4)', () => {
    const at = zonedTimeToUtc({ year: 2026, month: 7, day: 15, hour: 20, minute: 0 }, 'America/New_York');
    expect(at.toISOString()).toBe('2026-07-16T00:00:00.000Z');
  });

  it('keeps "every Monday 20:00" at 20:00 local across the spring-forward transition', () => {
    // US DST begins 2026-03-08 (2nd Sunday of March). The Monday before is
    // 2026-03-02 (still EST, UTC-5); the Monday after is 2026-03-09 (now EDT, UTC-4).
    const before = zonedTimeToUtc({ year: 2026, month: 3, day: 2, hour: 20, minute: 0 }, 'America/New_York');
    const after = zonedTimeToUtc({ year: 2026, month: 3, day: 9, hour: 20, minute: 0 }, 'America/New_York');

    expect(before.toISOString()).toBe('2026-03-03T01:00:00.000Z');
    expect(after.toISOString()).toBe('2026-03-10T00:00:00.000Z');

    // The whole point of storing local-time-plus-zone (PRD §9): both
    // instants read back as 20:00 local, even though they're 23 real hours
    // apart rather than 24.
    expect(utcToLocalDateHour(before, 'America/New_York')).toBe(20);
    expect(utcToLocalDateHour(after, 'America/New_York')).toBe(20);
  });

  it('keeps "every Monday 20:00" at 20:00 local across the fall-back transition', () => {
    // US DST ends 2026-11-01 (1st Sunday of November).
    const before = zonedTimeToUtc({ year: 2026, month: 10, day: 26, hour: 20, minute: 0 }, 'America/New_York');
    const after = zonedTimeToUtc({ year: 2026, month: 11, day: 2, hour: 20, minute: 0 }, 'America/New_York');

    expect(utcToLocalDateHour(before, 'America/New_York')).toBe(20);
    expect(utcToLocalDateHour(after, 'America/New_York')).toBe(20);
    // Confirms the UTC gap actually widened by an hour across the transition
    // (25 real hours apart instead of 24) rather than silently staying fixed.
    expect(after.getTime() - before.getTime()).toBe((7 * 24 + 1) * 60 * 60 * 1000);
  });
});

describe('utcToLocalDate', () => {
  it('reads back the same calendar date used to build the instant, in the same zone', () => {
    const at = zonedTimeToUtc({ year: 2026, month: 9, day: 15, hour: 8, minute: 0 }, 'Asia/Tokyo');
    expect(utcToLocalDate(at, 'Asia/Tokyo')).toEqual({ year: 2026, month: 9, day: 15 });
  });

  it('can disagree with the UTC calendar date near a day boundary', () => {
    // 08:00 JST on Sep 1 is still Aug 31 in UTC.
    const at = zonedTimeToUtc({ year: 2026, month: 9, day: 1, hour: 8, minute: 0 }, 'Asia/Tokyo');
    expect(utcToLocalDate(at, 'UTC')).toEqual({ year: 2026, month: 8, day: 31 });
  });
});

function utcToLocalDateHour(instant: Date, timeZone: string): number {
  const parts = new Intl.DateTimeFormat('en-US', { timeZone, hourCycle: 'h23', hour: '2-digit' }).formatToParts(instant);
  return Number(parts.find((p) => p.type === 'hour')?.value ?? NaN);
}
