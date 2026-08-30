import type { Occurrence } from '../../scheduleEngine/types';
import { computeReminderAt, deterministicNotificationId } from '../reminderTiming';

const sep1Occurrence: Occurrence = {
  occurrenceAt: new Date('2026-08-31T23:00:00.000Z'), // 2026-09-01 08:00 JST
  localDate: '2026-09-01',
};

describe('computeReminderAt', () => {
  it('places "night_before" at 20:00 local the previous calendar day', () => {
    const reminderAt = computeReminderAt(sep1Occurrence, { preset: 'night_before' }, 'Asia/Tokyo');
    expect(reminderAt.toISOString()).toBe('2026-08-31T11:00:00.000Z');
  });

  it('crosses a month boundary correctly for "night_before"', () => {
    // Occurrence localDate is the 1st of the month — previous day is the
    // last day of the previous month, not day "0".
    const reminderAt = computeReminderAt(sep1Occurrence, { preset: 'night_before' }, 'Asia/Tokyo');
    expect(reminderAt.toISOString()).toBe('2026-08-31T11:00:00.000Z');
  });

  it('places "same_day_morning" at 08:00 local the same calendar day', () => {
    const reminderAt = computeReminderAt(sep1Occurrence, { preset: 'same_day_morning' }, 'Asia/Tokyo');
    expect(reminderAt.toISOString()).toBe('2026-08-31T23:00:00.000Z');
  });

  it('places "custom" a fixed duration before the occurrence, independent of calendar/timezone', () => {
    const reminderAt = computeReminderAt(sep1Occurrence, { preset: 'custom', customMinutesBefore: 180 }, 'Asia/Tokyo');
    expect(reminderAt.toISOString()).toBe('2026-08-31T20:00:00.000Z');
  });

  it('throws for "custom" without customMinutesBefore', () => {
    expect(() => computeReminderAt(sep1Occurrence, { preset: 'custom' }, 'Asia/Tokyo')).toThrow();
  });

  it('keeps "night_before" 20:00 local correct across a DST transition day', () => {
    // Occurrence is Mon Mar 9 20:00 America/New_York (EDT). "Previous day"
    // is Mar 8 — the transition itself happens at 2am that day, so by the
    // reminder's own 20:00 the zone is already in EDT (UTC-4).
    const occurrence: Occurrence = {
      occurrenceAt: new Date('2026-03-10T00:00:00.000Z'),
      localDate: '2026-03-09',
    };
    const reminderAt = computeReminderAt(occurrence, { preset: 'night_before' }, 'America/New_York');
    expect(reminderAt.toISOString()).toBe('2026-03-09T00:00:00.000Z');
  });
});

describe('deterministicNotificationId', () => {
  it('is stable for the same inputs', () => {
    const a = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'night_before' });
    const b = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'night_before' });
    expect(a).toBe(b);
  });

  it('differs when the reminder preset differs', () => {
    const a = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'night_before' });
    const b = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'same_day_morning' });
    expect(a).not.toBe(b);
  });

  it('differs when a custom offset differs, even though the preset name is the same', () => {
    const a = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'custom', customMinutesBefore: 30 });
    const b = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'custom', customMinutesBefore: 60 });
    expect(a).not.toBe(b);
  });

  it('differs when the item differs', () => {
    const a = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'night_before' });
    const b = deterministicNotificationId('item-2', sep1Occurrence, { preset: 'night_before' });
    expect(a).not.toBe(b);
  });

  it('differs when the occurrence instant differs — this is what lets an edit cancel exactly the stale notification (PRD §25)', () => {
    const laterOccurrence: Occurrence = { ...sep1Occurrence, occurrenceAt: new Date('2026-09-07T23:00:00.000Z') };
    const a = deterministicNotificationId('item-1', sep1Occurrence, { preset: 'night_before' });
    const b = deterministicNotificationId('item-1', laterOccurrence, { preset: 'night_before' });
    expect(a).not.toBe(b);
  });
});
