import { expandOccurrences } from '../occurrences';
import type { ScheduleRule } from '../types';

function isoAll(occurrences: { occurrenceAt: Date }[]): string[] {
  return occurrences.map((o) => o.occurrenceAt.toISOString());
}

describe('expandOccurrences — once', () => {
  it('returns the single occurrence when it falls inside the range', () => {
    const rule: ScheduleRule = { scheduleType: 'once', at: '2026-09-30T18:00:00+09:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-01-01T00:00:00Z'),
      to: new Date('2026-12-31T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual(['2026-09-30T09:00:00.000Z']);
  });

  it('returns nothing when the instant falls outside the range', () => {
    const rule: ScheduleRule = { scheduleType: 'once', at: '2026-09-30T18:00:00+09:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2027-01-01T00:00:00Z'),
      to: new Date('2027-12-31T00:00:00Z'),
    });

    expect(result).toEqual([]);
  });
});

describe('expandOccurrences — weekly', () => {
  it('generates every matching weekday in range (PRD §45 example: Tue/Fri 08:00)', () => {
    const rule: ScheduleRule = { scheduleType: 'weekly', weekdays: [2, 5], time: '08:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-08-30T00:00:00Z'),
      to: new Date('2026-09-14T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual([
      '2026-08-31T23:00:00.000Z', // Tue Sep 1 JST
      '2026-09-03T23:00:00.000Z', // Fri Sep 4 JST
      '2026-09-07T23:00:00.000Z', // Tue Sep 8 JST
      '2026-09-10T23:00:00.000Z', // Fri Sep 11 JST
    ]);
  });

  it('stays DST-correct across a US spring-forward transition', () => {
    // Every Monday 20:00 America/New_York, spanning the 2026-03-08 transition.
    const rule: ScheduleRule = { scheduleType: 'weekly', weekdays: [1], time: '20:00' };
    const result = expandOccurrences(rule, 'America/New_York', {
      from: new Date('2026-03-01T00:00:00Z'),
      to: new Date('2026-03-15T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual([
      '2026-03-03T01:00:00.000Z', // Mon Mar 2, still EST (UTC-5)
      '2026-03-10T00:00:00.000Z', // Mon Mar 9, now EDT (UTC-4)
    ]);
  });
});

describe('expandOccurrences — monthly_day', () => {
  it('clamps day 31 to each month\'s actual length', () => {
    const rule: ScheduleRule = { scheduleType: 'monthly_day', day: 31, time: '08:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-01-01T00:00:00Z'),
      to: new Date('2026-05-01T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual([
      '2026-01-30T23:00:00.000Z', // Jan 31
      '2026-02-27T23:00:00.000Z', // clamped to Feb 28 (2026 is not a leap year)
      '2026-03-30T23:00:00.000Z', // Mar 31
      '2026-04-29T23:00:00.000Z', // clamped to Apr 30
    ]);
  });
});

describe('expandOccurrences — monthly_nth_weekday', () => {
  it('finds the 2nd Thursday of each month (PRD §45 example shape)', () => {
    const rule: ScheduleRule = { scheduleType: 'monthly_nth_weekday', nth: 2, weekday: 4, time: '08:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-09-01T00:00:00Z'),
      to: new Date('2026-11-30T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual(['2026-09-09T23:00:00.000Z', '2026-10-07T23:00:00.000Z', '2026-11-11T23:00:00.000Z']);
  });

  it('skips a month that does not have a 5th occurrence of that weekday', () => {
    // February 2026 has only four Fridays (see calendar.test.ts).
    const rule: ScheduleRule = { scheduleType: 'monthly_nth_weekday', nth: 5, weekday: 5, time: '08:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-02-01T00:00:00Z'),
      to: new Date('2026-02-28T00:00:00Z'),
    });

    expect(result).toEqual([]);
  });
});

describe('expandOccurrences — yearly', () => {
  it('clamps Feb 29 to Feb 28 in non-leap years and keeps Feb 29 in leap years', () => {
    const rule: ScheduleRule = { scheduleType: 'yearly', month: 2, day: 29, time: '08:00' };
    const result = expandOccurrences(rule, 'Asia/Tokyo', {
      from: new Date('2026-01-01T00:00:00Z'),
      to: new Date('2028-12-31T00:00:00Z'),
    });

    expect(isoAll(result)).toEqual([
      '2026-02-27T23:00:00.000Z', // 2026 not leap -> clamped to Feb 28
      '2027-02-27T23:00:00.000Z', // 2027 not leap -> clamped to Feb 28
      '2028-02-28T23:00:00.000Z', // 2028 is leap -> real Feb 29
    ]);
  });
});
