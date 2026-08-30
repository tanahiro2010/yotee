using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

/**
 * NOT part of MVP — see the header comment in `models/marketplace.tsp`.
 * Included only so the eventual shape of these endpoints is on record; do
 * not implement against this until Marketplace is actually scheduled.
 */
@tag("Marketplace (future — not in MVP)")
@useAuth(BearerAuth)
interface Marketplace {
  /** The Product a Category is listed under, if it has been put up for sale. */
  @get
  @route("/categories/{categoryId}/product")
  getProduct(@path categoryId: string): Product | ErrorResponse;

  /**
   * Start a purchase of a Category's Product. Returns a `pending` Purchase;
   * the client completes payment through the platform's native purchase flow
   * (App Store / Google Play) or a web checkout, and the server confirms
   * completion out-of-band (receipt validation / webhook — not modeled here,
   * pending the payment scheme design in PRD §63).
   */
  @post
  @route("/categories/{categoryId}/purchases")
  purchase(@path categoryId: string): {
    @statusCode statusCode: 201;
    @body purchase: Purchase;
  } | ErrorResponse;

  /** Every Entitlement the caller currently holds — gates access to paid Categories' updates. */
  @get
  @route("/me/entitlements")
  listEntitlements(): Entitlement[] | ErrorResponse;
}
