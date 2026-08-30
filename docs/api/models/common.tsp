namespace Yotee;

/**
 * Uniform error shape used by every non-2xx response (PRD §52).
 * There is no other envelope: success responses return the resource directly.
 */
@error
model ErrorResponse {
  error: ErrorDetail;
}

model ErrorDetail {
  /** Stable machine-readable error code, e.g. `CATEGORY_NOT_FOUND`. */
  code: string;

  /** Human-readable message, safe to surface in logs but not guaranteed translated. */
  message: string;
}

/** Shared cursor-paginated list envelope for search-style endpoints. */
model CursorPage<T> {
  items: T[];

  /** Opaque cursor for the next page; absent once there are no more results. */
  nextCursor?: string;
}
