import type { SQLiteDatabase } from 'expo-sqlite';

import { expandOccurrences } from '../domain/scheduleEngine';
import { listCategories } from '../data/categoryRepository';
import { listAllItems } from '../data/itemRepository';
import type { Category, Item } from '../data/models';

export interface UpcomingEntry {
  item: Item;
  category: Category;
  occurrenceAt: Date;
}

/**
 * Every Occurrence across every List within [from, to] — what the Home
 * timeline (PRD §13) renders, date-grouped, with the soonest one highlighted
 * (see the "Yotee UI decisions" project memory for why: 案D).
 */
export async function listUpcomingOccurrences(db: SQLiteDatabase, from: Date, to: Date): Promise<UpcomingEntry[]> {
  const [categories, items] = await Promise.all([listCategories(db), listAllItems(db)]);
  const categoryById = new Map(categories.map((category) => [category.id, category]));

  const entries: UpcomingEntry[] = [];
  for (const item of items) {
    const category = categoryById.get(item.categoryId);
    if (!category) {
      continue;
    }

    const occurrences = expandOccurrences(item.scheduleRule, category.timezone, { from, to });
    for (const occurrence of occurrences) {
      entries.push({ item, category, occurrenceAt: occurrence.occurrenceAt });
    }
  }

  entries.sort((a, b) => a.occurrenceAt.getTime() - b.occurrenceAt.getTime());
  return entries;
}
