import { useFocusEffect } from 'expo-router';
import { useSQLiteContext } from 'expo-sqlite';
import { useCallback, useState } from 'react';

import { listCategories } from '@/data/categoryRepository';
import type { Category } from '@/data/models';

export function useCategories() {
  const db = useSQLiteContext();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let cancelled = false;
      setLoading(true);

      listCategories(db)
        .then((result) => {
          if (!cancelled) {
            setCategories(result);
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

  return { categories, loading };
}
