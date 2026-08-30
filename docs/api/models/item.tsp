namespace Yotee;

/**
 * A single 予定 (Item) inside a List (PRD §44 `items`). MVP requires only
 * `name` and a schedule — everything else is optional (§7).
 *
 * Per-subscriber notification ON/OFF (§12) is intentionally absent here: it
 * is device-local state, never written back to the server.
 */
model Item {
  @format("uuid")
  id: string;

  @format("uuid")
  categoryId: string;

  name: string;
  description?: string;

  scheduleType: ScheduleType;
  scheduleRule: ScheduleRule;

  location?: string;
  @format("uri")
  url?: string;

  /** Manual display ordering within the List. */
  sortOrder: int32;

  createdAt: utcDateTime;
  updatedAt: utcDateTime;

  /** Present only once the Item has been soft-deleted (PRD §21). */
  deletedAt?: utcDateTime;
}

model CreateItemRequest {
  name: string;
  description?: string;
  scheduleRule: ScheduleRule;
  location?: string;
  @format("uri")
  url?: string;
  sortOrder?: int32;
}

/** All fields optional — PATCH is a partial update. */
model UpdateItemRequest {
  name?: string;
  description?: string;
  scheduleRule?: ScheduleRule;
  location?: string;
  @format("uri")
  url?: string;
  sortOrder?: int32;
}
