namespace Yotee;

/**
 * ---
 * NOT part of MVP. Marketplace/paid Categories are explicitly out of scope
 * through Phase 5 (PRD §60, §70/§71; CLAUDE.md product principle #5 "No
 * Marketplace/payments in MVP"). These models exist only because PRD §60
 * asks the data model to be designed for Category -> Product -> Purchase ->
 * Entitlement ahead of time so it doesn't need reshaping later — not because
 * any of this is being built or shipped now.
 *
 * Do not wire this up to a real payment provider before the legal/payment
 * scheme in PRD §63 is designed separately: which of App Store IAP / Google
 * Play Billing / Stripe is used, Creator payouts, Marketplace fees, 資金移動業
 * (funds transfer business licensing) exposure, tax handling, minors selling,
 * and refunds are all explicitly unconfirmed in PRD v0.1.
 * ---
 */

enum PurchaseStatus {
  pending: "pending",
  completed: "completed",
  refunded: "refunded",
  failed: "failed",
}

/**
 * A Category listed for sale (PRD §60, §61). Buy-once only — no recurring
 * billing model exists in the PRD, so there is deliberately no "plan"/
 * "interval" concept here. A Category that outgrows a single indefinite sale
 * is re-listed as a new Category per PRD §62's "Edition" pattern
 * (e.g. `○○ WORLD TOUR 2027` vs `○○ WORLD TOUR 2028`) rather than versioned
 * in place.
 */
model Product {
  @format("uuid")
  id: string;

  @format("uuid")
  categoryId: string;

  /** Buy-once price in the smallest unit of `currency` (e.g. whole yen for `JPY`). */
  @minValue(0)
  price: int32;

  /** ISO 4217 currency code. PRD's own example (§61) is `JPY`. */
  currency: string;

  createdAt: utcDateTime;
  updatedAt: utcDateTime;
}

/**
 * One purchase attempt/record for a Product. Deliberately provider-agnostic:
 * which payment rail(s) Marketplace will actually use is unconfirmed (§63).
 */
model Purchase {
  @format("uuid")
  id: string;

  @format("uuid")
  productId: string;

  status: PurchaseStatus;

  /**
   * Opaque reference into whichever payment provider processed this purchase
   * (App Store transaction id / Play Billing purchase token / Stripe payment
   * intent id, etc). Intentionally untyped further until §63 is resolved.
   */
  paymentReference?: string;

  createdAt: utcDateTime;
}

/**
 * The actual "receives this Category's updates" grant, created once a
 * Purchase completes (PRD §60). Plays the same role for paid Categories that
 * `Subscription` plays for free ones — kept as its own model rather than
 * folded into Subscription so free and paid access can evolve independently.
 */
model Entitlement {
  @format("uuid")
  id: string;

  @format("uuid")
  categoryId: string;

  @format("uuid")
  purchaseId: string;

  createdAt: utcDateTime;
}
