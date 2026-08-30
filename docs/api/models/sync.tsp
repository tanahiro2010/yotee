namespace Yotee;

/**
 * Delta-sync response (PRD §49). Only records changed since `cursor` are
 * returned, scoped to Categories the caller owns or is subscribed to — the
 * public catalog is never synced this way (§50); use `/categories/search`.
 */
model SyncResponse {
  categories: Category[];
  items: Item[];
  subscriptions: Subscription[];

  /** Opaque cursor to pass as `?cursor=` on the next call. */
  nextCursor: string;
}
