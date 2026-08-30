import { useFocusEffect } from 'expo-router';
import { useSQLiteContext } from 'expo-sqlite';
import { useCallback, useState } from 'react';

import { listUpcomingOccurrences, type UpcomingEntry } from '@/services/upcomingItems';

const HOME_WINDOW_DAYS = 60;

/** Re-loads on every screen focus — cheap (single-device SQLite reads), and the simplest way to reflect an Item just added/edited elsewhere. */
export function useUpcomingItems() {
  const db = useSQLiteContext();
  const [entries, setEntries] = useState<UpcomingEntry[]>([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let cancelled = false;
      setLoading(true);

      const now = new Date();
      const to = new Date(now.getTime() + HOME_WINDOW_DAYS * 24 * 60 * 60 * 1000);

      listUpcomingOccurrences(db, now, to)
        .then((result) => {
          if (!cancelled) {
            setEntries(result);
          }
        })
        .finally(() => {
          if (!cancelled) {
            setLoading(false);
          }
        });

      return () => {
        cancelled = true;
      };
    }, [db]),
  );

  return { entries, loading };
}
