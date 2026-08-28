# Git Workflow Reference

This project was built and delivered as a single overlay rather than
as a live, phase-by-phase git history — so there's no commit log to
inherit. If you're pushing this to your own GitHub repo for a
portfolio, section 19/20 of the original spec suggested a branch
structure and commit style; here's how to apply it to what's actually
in this folder.

## Suggested branch structure

```
main
develop
feature/authentication
feature/products
feature/cart
feature/orders
feature/vendor-dashboard
feature/admin-dashboard
feature/reviews
feature/notifications
feature/testing
```

A reasonable way to actually produce this history from a single
delivered folder: create `develop`, then cherry-pick this project's
directories onto feature branches roughly matching the phase
boundaries below, commit each, then merge each into `develop` and
finally into `main`. The phase boundaries in `README.md` map cleanly
onto these branches:

| Feature branch | Corresponds to |
|---|---|
| `feature/authentication` | Phase 2 |
| `feature/products` | Phase 3 |
| `feature/cart` | Phase 4 (cart/wishlist/checkout) |
| `feature/orders` | Phase 5 |
| `feature/vendor-dashboard` | Phase 6 |
| `feature/admin-dashboard` | Phase 7 |
| `feature/reviews` | The review flow spans Phases 1 (schema), 7 (moderation), 8 (submission) |
| `feature/notifications` | Phases 8–9 |
| `feature/testing` | Phase 10 |

## Commit message examples

Straight from spec section 20 — these read naturally against what was
actually built, phase by phase:

```
feat: implement vendor registration workflow
feat: add product variation management
feat: implement server-backed shopping cart
feat: add transactional checkout flow
feat: implement vendor commission calculation
feat: add product search and filtering
feat: implement real-time order notifications
fix: prevent ordering unavailable inventory
test: add order authorization tests
```

Matching this project's actual build, a few more that fit the same
style:

```
feat: add vendor approval workflow with pending/approved/suspended states
feat: build customer-vendor chat with Reverb broadcasting
feat: add low-stock and new-order queued notifications
fix: correct N+1 bug in product thumbnail eager loading
perf: add composite indexes for sales and commission reports
docs: add deployment guide and ER diagram
```

## Conventional prefixes used above

- `feat:` — a new feature or capability
- `fix:` — a bug fix (the `limit(1)` eager-load bug from Phase 11 is
  the clearest real example in this codebase)
- `perf:` — a performance-only change (no behavior change)
- `test:` — adding or changing tests only
- `docs:` — documentation only
- `refactor:` — restructuring without changing behavior
- `chore:` — tooling, dependencies, config

None of this is enforced by tooling in this repo (no commitlint/husky
configured) — it's a convention to follow by hand, or wire up if you
want it enforced.
