function toLocalDayKey(date: Date): string {
  return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
}

/** "今日" / "明日" / "9月3日" — grouping headers for the Home timeline (PRD §13's own example). Relative to the device's local calendar day, not any List's timezone (a display/grouping concern, not a scheduling one). */
export function dateHeaderLabel(date: Date, now: Date = new Date()): string {
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const tomorrow = new Date(today.getTime() + 24 * 60 * 60 * 1000);

  if (toLocalDayKey(date) === toLocalDayKey(today)) {
    return '今日';
  }
  if (toLocalDayKey(date) === toLocalDayKey(tomorrow)) {
    return '明日';
  }

  return `${date.getMonth() + 1}月${date.getDate()}日`;
}

export function dayGroupKey(date: Date): string {
  return toLocalDayKey(date);
}

/** "あと1日" / "あと3日" countdown pill text for the nearest upcoming item. */
export function countdownLabel(occurrenceAt: Date, now: Date = new Date()): string {
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const target = new Date(occurrenceAt.getFullYear(), occurrenceAt.getMonth(), occurrenceAt.getDate());
  const days = Math.round((target.getTime() - today.getTime()) / (24 * 60 * 60 * 1000));

  if (days <= 0) {
    return '本日';
  }
  return `あと${days}日`;
}

export function formatTime(date: Date): string {
  return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
}
