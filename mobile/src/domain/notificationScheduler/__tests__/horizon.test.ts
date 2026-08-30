import type { Occurrence } from '../../scheduleEngine/types';
import { buildReminderPlanForItem, selectWithinHorizon } from '../horizon';
import type { ReminderPlanEntry } from '../types';

function occurrenceAt(iso: string, localDate: string): Occurrence {
  return { occurrenceAt: new Date(iso), localDate };
}

describe('buildReminderPlanForItem', () => {
  it('produces one plan entry per occurrence, each with a deterministic id and computed reminder time', () => {
    const occurrences = [occurrenceAt('2026-08-31T23:00:00.000Z', '2026-09-01'), occurrenceAt('2026-09-03T23:00:00.000Z', '2026-09-04')];

    const plan = buildReminderPlanForItem('item-1', occurrences, { preset: 'night_before' }, 'Asia/Tokyo');

    expect(plan).toHaveLength(2);
    expect(plan[0].occurrenceAt).toEqual(occurrences[0].occurrenceAt);
    expect(plan[0].reminderAt.toISOString()).toBe('2026-08-31T11:00:00.000Z');
    expect(plan[1].reminderAt.toISOString()).toBe('2026-09-03T11:00:00.000Z');
    // Both entries carry the same itemId but distinct ids.
    expect(plan[0].itemId).toBe('item-1');
    expect(plan[0].id).not.toBe(plan[1].id);
  });
});

describe('selectWithinHorizon', () => {
  function entry(id: string, reminderAt: string): ReminderPlanEntry {
    return { id, itemId: 'item-1', occurrenceAt: new Date(reminderAt), reminderAt: new Date(reminderAt) };
  }

  it('drops entries whose reminder time has already passed', () => {
    const now = new Date('2026-09-01T00:00:00.000Z');
    const entries = [entry('past', '2026-08-31T00:00:00.000Z'), entry('future', '2026-09-02T00:00:00.000Z')];

    expect(selectWithinHorizon(entries, now).map((e) => e.id)).toEqual(['future']);
  });

  it('sorts by soonest reminder first', () => {
    const now = new Date('2026-09-01T00:00:00.000Z');
    const entries = [entry('later', '2026-09-05T00:00:00.000Z'), entry('sooner', '2026-09-02T00:00:00.000Z')];

    expect(selectWithinHorizon(entries, now).map((e) => e.id)).toEqual(['sooner', 'later']);
  });

  it('caps the result at maxCount, keeping the soonest ones (PRD §24: 48 OS-registered at a time)', () => {
    const now = new Date('2026-09-01T00:00:00.000Z');
    const entries = Array.from({ length: 60 }, (_, i) => entry(`e${i}`, new Date(now.getTime() + (i + 1) * 60_000).toISOString()));

    const selected = selectWithinHorizon(entries, now, 48);

    expect(selected).toHaveLength(48);
    expect(selected[0].id).toBe('e0');
    expect(selected[47].id).toBe('e47');
  });
});
