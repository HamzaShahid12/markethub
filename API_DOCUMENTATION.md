# MarketHub — Route & API Reference

The original spec (section 9) sketched a conventional REST API under
`/api/*` for everything. What actually got built, and why, differs
slightly — documented honestly here rather than restating the
original plan as if it were the final shape.

## Why most of the app isn't a separate REST API

MarketHub is an **Inertia.js application**: Vue pages are server-
rendered props over standard Laravel routes (session-authenticated,
CSRF-protected), not a decoupled SPA calling a token-authenticated
API. Product CRUD, orders, categories, coupons, reviews, users — all
of it goes through `routes/web.php` controllers returning
`Inertia::render(...)`, the same pattern Laravel/Inertia starter kits
use throughout.

A small, genuine JSON API exists under `/api/*` **only** for the
handful of things that need an in-page AJAX call rather than a full
Inertia visit — cart/wishlist mutations, coupon validation, and the
notification unread-count poll. These are documented below because
they're real JSON endpoints with their own request/response contract;
the rest of the app is documented as Inertia page routes instead,
since "what JSON does it return" isn't the useful question for them —
"what page and props does it render" is.

If you need a fully separate, token-authenticated REST API (e.g. for
a future mobile client), Laravel Sanctum is already a listed
dependency in the recommended stack and every controller's logic is
already extracted into thin controllers + Policies + Actions
specifically so it's straightforward to add `Api\*` controllers that
call the same `CreateOrder`/`CancelOrder`/`SendMessage` actions
without duplicating business logic — that's exactly why those actions
exist as standalone classes rather than being inlined into controllers.

## JSON API (`/api/*`, session-authenticated, rate-limited)

All routes below require an authenticated session (`auth` middleware)
and are throttled — see `routes/web.php` and Phase 11's README section
for the exact limits.

| Method | Endpoint | Purpose | Phase |
|---|---|---|---|
| GET | `/api/cart` | Current user's cart items | 4 |
| POST | `/api/cart/items` | Add an item `{product_id, product_variant_id?, quantity}` | 4 |
| PUT | `/api/cart/items/{item}` | Update quantity `{quantity}` | 4 |
| DELETE | `/api/cart/items/{item}` | Remove an item | 4 |
| GET | `/api/wishlist` | Current user's wishlist items | 4 |
| POST | `/api/wishlist/toggle` | Add/remove `{product_id}` — returns `wishlisted: bool` | 4 |
| DELETE | `/api/wishlist/items/{item}` | Remove a wishlist item | 4 |
| POST | `/api/coupons/validate` | Validate `{code, subtotal}` — returns `{code, discount}` or 422 | 4 |
| GET | `/api/notifications/unread-count` | `{count}` for the bell badge | 8 |

Every cart/wishlist response returns the **full current list**, not a
delta — the Pinia stores (`resources/js/Stores/cart.js`,
`wishlist.js`) just replace their state wholesale, so there's no
client-side merge logic to get wrong.

## Inertia page routes (the actual application)

Grouped by role — auth/role gating is enforced by the `auth` and
`role:*` middleware (Phase 2), not just hidden in the UI.

### Public / guest

| Route | Renders | Phase |
|---|---|---|
| `GET /` | `Storefront/Home` | 1 |
| `GET /products` | `Storefront/Products/Index` (search/filter/sort) | 3 |
| `GET /products/{slug}` | `Storefront/Products/Show` | 3 |
| `GET /categories` | `Storefront/Categories/Index` | 3 |
| `GET /categories/{slug}` | `Storefront/Products/Index` (category-locked) | 3 |
| `GET /register`, `GET /login` | Breeze auth pages, customized `Auth/Register` | 2 |

### Authenticated, any role

| Route | Renders | Phase |
|---|---|---|
| `GET /dashboard` | Redirects to the role's own dashboard | 2 |
| `GET /cart`, `GET /wishlist` | `Storefront/Cart`, `Storefront/Wishlist` | 4 |
| `GET /checkout`, `POST /checkout` | `Storefront/Checkout` → `CreateOrder` action | 4 |
| `GET /orders/{orderNumber}/success` | `Storefront/OrderSuccess` | 4 |
| `GET /notifications` | `Notifications/Index` (role-aware layout) | 8 |

### Customer (`role:customer`)

| Route | Renders / Action | Phase |
|---|---|---|
| `GET /customer/dashboard` | `Customer/Dashboard` | 2 |
| `GET /customer/orders`, `/customer/orders/{order}` | Order history/detail | 5 |
| `POST /customer/orders/{order}/cancel` | `CancelOrder` action | 5 |
| `POST /customer/reviews` | Submit a review (delivered-purchase gated) | 8 |
| `GET /customer/messages`, `/customer/messages/{conversation}` | Chat inbox/thread | 9 |
| `POST /customer/messages/start/{vendor}` | Start/resume a conversation | 9 |

### Vendor (`role:vendor`)

| Route | Renders / Action | Phase |
|---|---|---|
| `GET /vendor/dashboard` | Pending-approval or live stats | 2 |
| `GET|POST|PUT|DELETE /vendor/products*` | Full product CRUD (resource route) | 3 |
| `GET /vendor/inventory`, `PUT .../products/{p}`, `PUT .../variants/{v}` | Stock management | 6 |
| `GET /vendor/orders`, `/vendor/orders/{order}` | Scoped order view | 5 |
| `PUT /vendor/orders/{order}/items/{item}/status` | Per-item status update | 5 |
| `GET /vendor/sales`, `GET /vendor/earnings` | Analytics/commission report | 6 |
| `GET|PUT /vendor/store-profile` | Shop branding (not status/commission) | 6 |
| `GET /vendor/messages*` | Chat inbox/thread | 9 |

### Admin (`role:admin`)

| Route | Renders / Action | Phase |
|---|---|---|
| `GET /admin/dashboard` | Platform overview | 2 |
| `GET /admin/vendors`, `POST .../approve|reject|suspend` | Vendor approval workflow | 2 |
| `GET|POST|PUT|DELETE /admin/categories*` | Category CRUD | 3 |
| `GET /admin/orders`, `/admin/orders/{order}`, `PUT .../status` | Order oversight | 5, 8 |
| `GET /admin/users`, `POST .../toggle-status` | User management | 7 |
| `GET /admin/products`, `PUT .../status` | Product moderation | 7 |
| `GET|POST|PUT|DELETE /admin/coupons*` | Coupon CRUD | 7 |
| `GET /admin/reviews`, `PUT .../status` | Review moderation | 7 |
| `GET /admin/commissions` | Platform-wide commission report | 7 |
| `GET /admin/reports` | GMV/orders/top-vendor/top-category analytics | 7 |

## Real-time (Reverb, Phase 9)

Not HTTP endpoints — private WebSocket channels, authorized in
`routes/channels.php`:

| Channel | Purpose |
|---|---|
| `private-App.Models.User.{id}` | Broadcast notifications (Laravel's default, auto-authorized) |
| `private-conversations.{id}` | Live chat messages, authorized against the two participants |

## Authentication

Session-based (Laravel's default `web` guard + Breeze), not token
auth — every route above runs inside the standard CSRF-protected web
middleware group. `Laravel\Sanctum` is listed in the recommended
stack and already a natural fit if a token-authenticated API (mobile
app, third-party integration) gets added later, but nothing in this
build currently issues API tokens.
