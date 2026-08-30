using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

/**
 * Item mutation endpoints live directly under `/items/{itemId}` rather than
 * nested under their Category (PRD §51) — the client already knows the Item
 * id from a synced/fetched Category, so the parent id is redundant here.
 * Item creation, by contrast, is nested: see `Categories.createItem`.
 */
@tag("Items")
@route("/items")
@useAuth(BearerAuth)
interface Items {
  /** Partial update. Bumps the parent Category's `version` and enqueues the `category.updated` push (PRD §20, §37). */
  @patch
  @route("/{itemId}")
  update(@path itemId: string, @body body: UpdateItemRequest): Item | ErrorResponse;

  /** Soft-delete (PRD §21). */
  @delete
  @route("/{itemId}")
  delete(@path itemId: string): {
    @statusCode statusCode: 204;
  } | ErrorResponse;
}
