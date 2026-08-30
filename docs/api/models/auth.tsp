namespace Yotee;

/** PRD §53 — Social Login only, no first-party email/password. */
enum AuthProvider {
  google: "google",
  apple: "apple",
}

model LoginRequest {
  provider: AuthProvider;

  /**
   * The provider's identity token (Google/Apple Sign-In). Verified server-side;
   * scoped to identity only — no Calendar or other provider scopes are requested.
   */
  idToken: string;
}

model AuthTokens {
  accessToken: string;
  refreshToken: string;

  /** Access token lifetime in seconds. */
  expiresIn: int32;
}

model LoginResponse {
  ...AuthTokens;
  user: User;
}

model RefreshTokenRequest {
  refreshToken: string;
}

model LogoutRequest {
  refreshToken: string;
}
