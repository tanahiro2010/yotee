using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

@tag("Sync")
@route("/sync")
@useAuth(BearerAuth)
interface Sync {
  /**
   * Delta sync (PRD §49, §50). Returns only Categories/Items/Subscriptions
   * changed since `cursor`, scoped to what the caller owns or is subscribed
   * to. Called on app foreground, manual refresh and background task — never
   * only in response to a push, since OS push delivery isn't guaranteed (§27).
   */
  @get
  sync(@query cursor?: string): SyncResponse | ErrorResponse;
}
