# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository status

This repository currently contains only the product/design spec — [予定購読・通知アプリ 詳細PRD.md](予定購読・通知アプリ 詳細PRD.md) (in Japanese). No code, build tooling, or dependency manifests exist yet. There are no build/lint/test commands to run because no project has been scaffolded. When starting implementation, follow the "Frontend/Backend structure" and "Implementation phases" sections below, and update this file with real commands (`npm run ...`, `composer ...`, etc.) once `package.json` / `composer.json` exist.

Per the PRD's phased plan (§75), build in this order: Phase 1 local-only mobile prototype (no backend) → Phase 2 backend + sync → Phase 3 public/discover/subscribe → Phase 4 update push/reschedule → Phase 5 production hardening. See [ROADMAP.md](ROADMAP.md) for the task-level breakdown of each phase.

## GitHub workflow

Issues for the *next* phase are filed only once the *current* phase is complete — do not batch-file issues for future phases in advance. Each phase has a GitHub milestone (`Phase 1`–`Phase 5`) and a matching label (`phase-1`–`phase-5`); file new tasks against the milestone/label of the phase currently in progress.

## Product summary

**Yotee (ヨティー)** — not a calendar app. Users create or subscribe to "future schedule lists" (e.g. trash-collection days, concert ticket sales, game release dates) and get notified at the right time. The core value proposition is *not having to remember or manually re-enter recurring/one-off dates yourself*. Long-term direction is a "Schedule Marketplace" where creators can sell schedule lists, but this is explicitly out of MVP scope.

The user-facing model has exactly two concepts: **リスト (List)** and **予定 (Item/Schedule)**. Internal domain terms (`Category`, `Item`, `Schedule Rule`, `Occurrence`, `Notification`) must never leak into UI copy — `Category` is the internal name for what users see as "リスト".

## Core architectural principle (do not violate)

**The backend never executes reminder notifications and never runs a scheduling cron.** This is the single most load-bearing decision in the PRD (§22, §65, §77) and any implementation must preserve it:

- **Server = source of truth.** Stores `User`, `Category`, `Item`, `Subscription`, `Device` and handles auth, CRUD, publishing, search, subscription, and sync. It does not compute occurrences or fire reminders.
- **Smartphone = computation and execution.** The device expands `Schedule Rule` → future `Occurrence`s → reminder datetimes, and registers OS-level Local Notifications itself. This keeps backend load flat regardless of how many users subscribe to the same schedule (§65 Scalability: "100万人が同じ予定を購読していても、毎日の100万通知をServer Cronから送信する構造にはしない").
- **Push notifications are not used for reminders.** Their only role is telling a subscribed device "this Category changed, go re-sync" (`category.updated` with new `version`). Push delivery is explicitly not treated as a sync guarantee (OS-level delivery isn't guaranteed) — sync must also happen on app foreground, manual refresh, and background task, not rely on push alone (§27).

When implementing sync, notification scheduling, or any server-side "send a reminder" feature request, check it against this split before proceeding.

## Data model

Internal hierarchy: `Category → Item → Schedule Rule → Occurrence → Notification`, exposed to users only as `List → Item`.

- **Category** (`categories` table): owns `visibility` (`private` / `unlisted` / `public`), `timezone` (IANA, e.g. `Asia/Tokyo`), `version` (incremented on any Item change — drives client sync/diffing), `recommended_reminder`. Soft-deleted via `deleted_at`.
- **Item** (`items` table): belongs to a Category; `schedule_type` + `schedule_rule` (MySQL JSON — shapes vary per rule type, see PRD §45), optional `location`/`url`. Soft-deleted via `deleted_at`. MVP requires only name + schedule.
- **Schedule Rule types (MVP):** `Once`, `Weekly` (weekdays array), `Monthly Day`, `Monthly Nth Weekday`, `Yearly`. Recurring rules are stored as **local time + IANA timezone**, never pre-converted to a fixed UTC cadence — this is required for DST correctness ("every Monday 20:00" must stay 20:00 local across DST transitions) (§9).
- **Subscription**: `UNIQUE(user_id, category_id)`. Per-user notification timing preferences are **not** stored server-side — they live only in the device's local SQLite (§11, §46), so the server never manages per-user reminder-time fan-out.
- **Device**: push token + platform (`ios`/`android`), used only to deliver `category.updated` pings.
- All public API entity IDs are UUID-style, not raw auto-increment DB IDs (§40).
- Deletes throughout the system are soft deletes (`deleted_at`) so subscribers can detect and reconcile removals rather than silently losing data.

## Notification scheduling design

- Client-side "Notification Scheduler" expands Schedule Rules into Occurrences, computes reminder datetimes from user prefs, and registers them with the OS.
- **Notification Horizon**: don't register a year of notifications at once. Default: compute 60 days ahead, cap at 48 OS-registered notifications at a time; backfill more on app launch / sync / background task (§24 — tune after on-device testing).
- **Deterministic Local Notification IDs**, derived from `itemId + occurrenceAt + reminderRule`, so that on Item update the client can reliably cancel the exact stale notification and register the replacement (§25).

## Sync model

- Client persists a sync cursor and calls `GET /sync?cursor=...`, receiving only changed `categories`/`items`/`subscriptions` plus `nextCursor` — delta sync, not full re-fetch (§49).
- Client only syncs Categories it **owns or is subscribed to** — never the full public catalog. "探す" (Discover/search) hits the API directly instead of syncing all public Categories to the device (§50).
- On detecting a version bump for a synced Category: fetch latest → write Items to SQLite → cancel the old Local Notifications for that Item → recompute future Occurrences → reschedule with the OS (§20).

## Planned structure

### Mobile (React Native + Expo + TypeScript + Expo Router)

```
src/app/
├── _layout.tsx
├── (tabs)/            # ホーム / 探す / マイリスト (3-tab MVP nav, §14)
│   ├── index.tsx
│   ├── discover.tsx
│   └── lists.tsx
├── lists/
│   ├── new.tsx
│   └── [categoryId]/{index,edit}.tsx
├── items/
│   ├── new.tsx
│   └── [itemId].tsx
└── settings/index.tsx
```

Key libs: `expo-notifications` (local + push), `expo-sqlite` (persistent client cache), `expo-secure-store` (auth tokens — Keychain/Keystore backed, never put secrets in SQLite), `expo-background-task`.

State is deliberately split three ways, no global state library:
- **Persistent data** (Category/Item/Subscription) → SQLite
- **Auth secrets** (access/refresh tokens) → SecureStore
- **UI-only state** (modals, in-progress forms, theme) → React state/Context

The Schedule Engine (rule → occurrence expansion) must be pure TypeScript, decoupled from UI, so it's unit-testable in isolation (§74) — this is the highest-value area for test coverage (weekday math, month-end, leap years, nth-weekday, DST).

### Backend (PHP + Slim 4 + MySQL 8 + PDO)

Layering is strict: `Route → Controller → Service → Repository → PDO → MySQL`. Slim carries no domain logic.

```
backend/
├── public/index.php
├── src/{Controller,Service,Repository,Domain,Middleware,Validation,Infrastructure}/
├── routes/
├── config/
├── migrations/
├── tests/
└── composer.json
```

- **Controller**: HTTP parsing, calls Service, builds response. No SQL, no business logic, no notification math.
- **Service**: business logic (e.g. `CategoryService.publish()`, `ItemService.update()` — Item updates must atomically bump `Category.version` and enqueue the update-push in the same Service call, §37).
- **Repository**: all PDO access lives here; nothing above it touches SQL directly.
- Every mutating endpoint on an owned resource must verify `resource.category.owner_id === currentUser.id` server-side — never trust an owner/permission field sent by the client (§67).
- Keep Slim patched for security fixes (PRD flags CVE-2026-48157 as the reason 4.15.2+ is required at time of writing — verify current advisories when bootstrapping `composer.json`).

### API

Base path `/api/v1`. Success responses return the resource directly (no envelope wrapper); errors use a uniform `{ "error": { "code", "message" } }` shape (§52). Route groups: `/auth/*`, `/categories/*` (+ `/categories/search`), `/categories/{id}/items`, `/categories/{id}/subscribe`, `/me/subscriptions`, `/devices`, `/sync`.

## Product principles (tie-breakers when scope is ambiguous — §77)

1. **Don't become a calendar app** — resist adding general schedule-management features.
2. **Minimize user input** — "just subscribe" beats "please fill this out," prefer subscribing to existing public Lists over manual entry.
3. **Don't expose complex settings by default** — advanced notification/schedule config is opt-in, hidden behind simple defaults (e.g. UI shows "前日の夜/当日の朝/カスタム", never cron syntax).
4. **Never give the backend reminder-execution responsibility** — see Core architectural principle above.
5. **No Marketplace/payments in MVP** — validate the subscribe-to-a-schedule experience first; design the data model to accommodate `Category → Product → Purchase → Entitlement` later, but do not build it now.

## Explicitly out of MVP scope

Paid Categories/Marketplace, Creator payouts, Stripe/App Store/Google Play purchase flows, Google Calendar integration, comments, follow, like, creator analytics, official-account verification, full web app, complex social features.
