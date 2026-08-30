namespace Yotee;

/**
 * A registered account (PRD §42 `users`). Intentionally minimal — the app
 * does not collect more personal data than it needs (§42, §68 Privacy).
 */
model User {
  @format("uuid")
  id: string;

  displayName: string;
  email: string;

  createdAt: utcDateTime;
  updatedAt: utcDateTime;
}
