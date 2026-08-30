using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

@tag("Subscriptions")
@route("/me/subscriptions")
@useAuth(BearerAuth)
interface Subscriptions {
  /** All Categories the caller currently subscribes to (PRD §51). */
  @get
  list(): SubscriptionWithCategory[] | ErrorResponse;
}
