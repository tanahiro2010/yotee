import { listDotColors } from './colors';

const COLORS = [listDotColors.green, listDotColors.purple, listDotColors.blue];

/**
 * Categories don't have a stored color (PRD's data model has no such
 * column) — this derives a *stable* pick from the three fixed identity
 * colors so the same List always renders the same color across renders and
 * sessions, without a schema change.
 */
export function getListColor(categoryId: string): string {
  let hash = 0;
  for (let i = 0; i < categoryId.length; i += 1) {
    hash = (hash * 31 + categoryId.charCodeAt(i)) | 0;
  }
  return COLORS[Math.abs(hash) % COLORS.length];
}
