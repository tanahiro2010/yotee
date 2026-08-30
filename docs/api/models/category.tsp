namespace Yotee;

/** Exposed to users as a "リスト" (List) — never call this a Category in UI copy. */
enum CategoryVisibility {
  private: "private",
  unlisted: "unlisted",
  public: "public",
}

enum ReminderPreset {
  /** 前日の夜 */
  nightBefore: "night_before",

  /** 当日の朝 */
  sameDayMorning: "same_day_morning",

  /** カスタム — only then does `customMinutesBefore` apply. */
  custom: "custom",
}

/**
 * Creator-supplied *suggested* reminder timing (PRD §11), copied into a
 * subscriber's device-local notification preference at subscribe time.
 * It is only ever a default — the server never stores per-subscriber
 * notification timing (that lives in the device's SQLite, PRD §46).
 */
model RecommendedReminder {
  preset: ReminderPreset;

  /** Required and only meaningful when `preset` is `custom`. */
  @minValue(1)
  customMinutesBefore?: int32;
}

/** A List as stored server-side (PRD §43 `categories`). */
model Category {
  @format("uuid")
  id: string;

  @format("uuid")
  ownerId: string;

  name: string;
  description?: string;
  visibility: CategoryVisibility;

  /** IANA timezone name, e.g. `Asia/Tokyo` (PRD §9). Governs every recurring Item's local time. */
  timezone: string;

  /**
   * Monotonically incremented on any Item change under this Category.
   * Drives client delta-sync and the `category.updated` push signal (PRD §20, §43).
   */
  @minValue(1)
  version: int32;

  recommendedReminder?: RecommendedReminder;

  createdAt: utcDateTime;
  updatedAt: utcDateTime;

  /** Present only once the List has been soft-deleted (PRD §21). */
  deletedAt?: utcDateTime;
}

/** `GET /categories/{id}` response — a Category plus its current Items. */
model CategoryDetail {
  ...Category;
  items: Item[];
}

model CreateCategoryRequest {
  name: string;
  description?: string;
  visibility: CategoryVisibility;
  timezone: string;
  recommendedReminder?: RecommendedReminder;
}

/** All fields optional — PATCH is a partial update. */
model UpdateCategoryRequest {
  name?: string;
  description?: string;
  visibility?: CategoryVisibility;
  timezone?: string;
  recommendedReminder?: RecommendedReminder;
}

model CategorySearchResult {
  ...CursorPage<Category>;
}
