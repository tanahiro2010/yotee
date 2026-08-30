using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

@tag("Categories")
@route("/categories")
interface Categories {
  /**
   * Search public Categories by name/description (PRD §57). This is the only
   * way the client sees the public catalog — results are never synced to the
   * device via `/sync` (§50).
   */
  @get
  @route("/search")
  search(@query q: string, @query cursor?: string, @query limit?: int32): CategorySearchResult | ErrorResponse;

  /** Create a new List, owned by the caller. */
  @post
  @useAuth(BearerAuth)
  create(@body body: CreateCategoryRequest): {
    @statusCode statusCode: 201;
    @body category: Category;
  } | ErrorResponse;

  /**
   * Fetch a List and its current Items. Visibility rules are enforced
   * server-side: `private` requires the owner's auth, `unlisted`/`public`
   * are readable by anyone holding the id/link.
   */
  @get
  @route("/{id}")
  get(@path id: string): CategoryDetail | ErrorResponse;

  /** Partial update. Bumps `Category.version` and enqueues the `category.updated` push (PRD §20, §37). */
  @patch
  @route("/{id}")
  @useAuth(BearerAuth)
  update(@path id: string, @body body: UpdateCategoryRequest): Category | ErrorResponse;

  /** Soft-delete (PRD §21) — subscribers detect this via `deletedAt` on their next sync. */
  @delete
  @route("/{id}")
  @useAuth(BearerAuth)
  delete(@path id: string): {
    @statusCode statusCode: 204;
  } | ErrorResponse;

  /** Add an Item to a List the caller owns. */
  @post
  @route("/{categoryId}/items")
  @useAuth(BearerAuth)
  createItem(@path categoryId: string, @body body: CreateItemRequest): {
    @statusCode statusCode: 201;
    @body item: Item;
  } | ErrorResponse;

  /** Subscribe the caller to a public/unlisted List (PRD §19). */
  @post
  @route("/{categoryId}/subscribe")
  @useAuth(BearerAuth)
  subscribe(@path categoryId: string): {
    @statusCode statusCode: 201;
    @body subscription: Subscription;
  } | ErrorResponse;

  /** Unsubscribe. */
  @delete
  @route("/{categoryId}/subscribe")
  @useAuth(BearerAuth)
  unsubscribe(@path categoryId: string): {
    @statusCode statusCode: 204;
  } | ErrorResponse;

  /**
   * Report a public List for moderation (PRD §58). Not enumerated under
   * `/api/v1` in PRD §51, but required by the §70 MVP "Public List Report"
   * safety requirement, so it is documented here as part of the Category
   * resource group.
   */
  @post
  @route("/{categoryId}/report")
  @useAuth(BearerAuth)
  report(@path categoryId: string, @body body: CreateReportRequest): {
    @statusCode statusCode: 204;
  } | ErrorResponse;
}
