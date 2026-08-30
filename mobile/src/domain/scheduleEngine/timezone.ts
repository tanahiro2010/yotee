/**
 * Converts a local wall-clock time in an arbitrary IANA timezone to a real
 * UTC instant, using only `Intl.DateTimeFormat` — deliberately no extra
 * timezone library dependency (Hermes/RN ships full ICU data, so this is
 * available without a polyfill). This is the one place DST correctness
 * lives: recurring rules are stored as local time + timezone, never a fixed
 * UTC cadence (PRD §9), so "every Monday 20:00" must keep meaning 20:00
 * local across a DST transition rather than drifting by an hour.
 *
 * Known limitation: the two times that are inherently ambiguous under any
 * clock — the skipped hour at a spring-forward transition, and the repeated
 * hour at a fall-back transition — don't have one uniquely correct UTC
 * instant by definition. This resolves both by picking whatever the
 * standard JS/ICU interpretation converges to rather than special-casing
 * either policy; occurrences.test.ts asserts the result lands within the
 * expected day rather than pinning an exact minute for those dates.
 */

export interface LocalDateTime {
  year: number;
  month: number; // 1-12
  day: number;
  hour: number;
  minute: number;
}

function offsetMinutesAt(utcMillis: number, timeZone: string): number {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    hourCycle: 'h23',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).formatToParts(new Date(utcMillis));

  const get = (type: string) => Number(parts.find((p) => p.type === type)?.value ?? '0');

  const asIfUtc = Date.UTC(get('year'), get('month') - 1, get('day'), get('hour'), get('minute'), get('second'));

  return (asIfUtc - utcMillis) / 60_000;
}

export function zonedTimeToUtc(local: LocalDateTime, timeZone: string): Date {
  const guessUtcMillis = Date.UTC(local.year, local.month - 1, local.day, local.hour, local.minute, 0);

  // First pass: the offset at our best guess.
  const offset1 = offsetMinutesAt(guessUtcMillis, timeZone);
  let utcMillis = guessUtcMillis - offset1 * 60_000;

  // Second pass: re-check the offset at the corrected instant, in case it
  // crossed a DST boundary — this converges correctly except within the
  // ambiguous hour itself (see the limitation note above).
  const offset2 = offsetMinutesAt(utcMillis, timeZone);
  if (offset2 !== offset1) {
    utcMillis = guessUtcMillis - offset2 * 60_000;
  }

  return new Date(utcMillis);
}

export function utcToLocalDate(instant: Date, timeZone: string): { year: number; month: number; day: number } {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(instant);

  const get = (type: string) => Number(parts.find((p) => p.type === type)?.value ?? '0');

  return { year: get('year'), month: get('month'), day: get('day') };
}
