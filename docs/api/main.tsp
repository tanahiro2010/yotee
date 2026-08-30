import "@typespec/http";
import "@typespec/openapi";
import "@typespec/openapi3";

import "./models/common.tsp";
import "./models/auth.tsp";
import "./models/user.tsp";
import "./models/schedule-rule.tsp";
import "./models/category.tsp";
import "./models/item.tsp";
import "./models/subscription.tsp";
import "./models/device.tsp";
import "./models/sync.tsp";
import "./models/report.tsp";
import "./models/marketplace.tsp";

import "./routes/auth.tsp";
import "./routes/categories.tsp";
import "./routes/items.tsp";
import "./routes/subscriptions.tsp";
import "./routes/devices.tsp";
import "./routes/sync.tsp";
import "./routes/marketplace.tsp";

using TypeSpec.Http;
using TypeSpec.OpenAPI;

/**
 * Yotee (ヨティー) HTTP API.
 *
 * The backend is the source of truth for Users/Categories/Items/Subscriptions/
 * Devices and handles auth, CRUD, publishing, search and sync. It never
 * computes Occurrences and never fires reminders itself — see CLAUDE.md
 * "Core architectural principle". Every domain concept below is internal;
 * user-facing copy only ever says "リスト" (List) and "予定" (Item).
 *
 * The `Marketplace (future — not in MVP)` tag covers Product/Purchase/
 * Entitlement endpoints that are speculative only — see the header comment
 * in `models/marketplace.tsp` before treating them as buildable.
 */
@service(#{ title: "Yotee API" })
@info(#{ version: "0.1.0" })
@server("https://api.yotee.net/api/v1", "Production")
namespace Yotee;
