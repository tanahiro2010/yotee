namespace Yotee;

/**
 * Links a user to a Category they've subscribed to (PRD §46 `subscriptions`).
 * `UNIQUE(user_id, category_id)` server-side; the current user is implied by
 * the auth context, never sent in the request body.
 *
 * Notification timing preferences are deliberately NOT part of this model —
 * they live only in the device's local SQLite (§11, §46), so subscribing
 * just copies the Category's `recommendedReminder` as a one-time default.
 */
model Subscription {
  @format("uuid")
  id: string;

  @format("uuid")
  categoryId: string;

  createdAt: utcDateTime;
}

/** `GET /me/subscriptions` embeds the Category so the client can render without a second round trip. */
model SubscriptionWithCategory {
  ...Subscription;
  category: Category;
}
