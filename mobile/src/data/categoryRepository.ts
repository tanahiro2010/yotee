import type { SQLiteDatabase } from 'expo-sqlite';
import * as Crypto from 'expo-crypto';

import type { CategoryVisibility } from '../domain/category';
import { categoryFromRow, type Category, type CategoryRow } from './models';

export interface CreateCategoryInput {
  name: string;
  description?: string | null;
  visibility: CategoryVisibility;
  timezone: string;
}

export async function listCategories(db: SQLiteDatabase): Promise<Category[]> {
  const rows = await db.getAllAsync<CategoryRow>('SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY created_at ASC');
  return rows.map(categoryFromRow);
}

export async function getCategory(db: SQLiteDatabase, id: string): Promise<Category | null> {
  const row = await db.getFirstAsync<CategoryRow>('SELECT * FROM categories WHERE id = ? AND deleted_at IS NULL', id);
  return row ? categoryFromRow(row) : null;
}

export async function createCategory(db: SQLiteDatabase, input: CreateCategoryInput): Promise<Category> {
  const id = Crypto.randomUUID();
  const now = new Date().toISOString();

  await db.runAsync(
    `INSERT INTO categories (id, name, description, visibility, timezone, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    id,
    input.name,
    input.description ?? null,
    input.visibility,
    input.timezone,
    now,
    now,
  );

  return { id, name: input.name, description: input.description ?? null, visibility: input.visibility, timezone: input.timezone, createdAt: now, updatedAt: now };
}

export async function deleteCategory(db: SQLiteDatabase, id: string): Promise<void> {
  await db.runAsync('UPDATE categories SET deleted_at = ? WHERE id = ?', new Date().toISOString(), id);
}
