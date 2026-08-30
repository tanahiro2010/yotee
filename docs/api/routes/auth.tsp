using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

@tag("Auth")
@route("/auth")
interface Auth {
  /**
   * Exchange a Google or Apple identity token for Yotee session tokens.
   * Creates the User on first login (PRD §53).
   */
  @post
  @route("/login")
  login(@body body: LoginRequest): LoginResponse | ErrorResponse;

  /** Exchange a refresh token for a new token pair. */
  @post
  @route("/refresh")
  refresh(@body body: RefreshTokenRequest): AuthTokens | ErrorResponse;

  /** Revoke a refresh token, ending that session. */
  @post
  @route("/logout")
  @useAuth(BearerAuth)
  logout(@body body: LogoutRequest): {
    @statusCode statusCode: 204;
  } | ErrorResponse;
}

@tag("Auth")
@route("/me")
@useAuth(BearerAuth)
interface Me {
  /** The currently authenticated user (PRD §51). */
  @get
  getCurrentUser(): User | ErrorResponse;
}
